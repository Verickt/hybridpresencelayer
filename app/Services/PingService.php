<?php

namespace App\Services;

use App\Events\MutualMatchCreated;
use App\Events\PingReceived;
use App\Exceptions\CooldownException;
use App\Exceptions\DuplicatePingException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PingService
{
    private const MAX_PINGS_PER_HOUR = 10;

    private const IGNORE_COOLDOWN = 3;

    public function send(User $sender, User $receiver, Event $event): Ping
    {
        if ($sender->id === $receiver->id) {
            throw new \InvalidArgumentException('You cannot ping yourself.');
        }

        $this->validateRateLimit($sender, $event);
        $this->validateNoDuplicate($sender, $receiver, $event);
        $this->validateCooldown($sender, $receiver, $event);

        return DB::transaction(function () use ($sender, $receiver, $event) {
            $ping = Ping::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'event_id' => $event->id,
                'status' => 'pending',
            ]);

            // Check for mutual match with lock to prevent race conditions
            $reciprocal = Ping::where('sender_id', $receiver->id)
                ->where('receiver_id', $sender->id)
                ->where('event_id', $event->id)
                ->where('status', 'pending')
                ->active()
                ->lockForUpdate()
                ->first();

            if ($reciprocal) {
                $ping->update(['status' => 'matched']);
                $reciprocal->update(['status' => 'matched']);

                $connection = $this->createConnection($sender, $receiver, $event);

                MutualMatchCreated::dispatch($event, $connection, $sender, $receiver);

                return $ping;
            }

            PingReceived::dispatch($event, $ping);

            return $ping;
        });
    }

    private function validateRateLimit(User $sender, Event $event): void
    {
        $recentCount = Ping::where('sender_id', $sender->id)
            ->where('event_id', $event->id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentCount >= self::MAX_PINGS_PER_HOUR) {
            throw new RateLimitExceededException;
        }
    }

    private function validateNoDuplicate(User $sender, User $receiver, Event $event): void
    {
        $exists = Ping::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('event_id', $event->id)
            ->active()
            ->exists();

        if ($exists) {
            throw new DuplicatePingException;
        }
    }

    private function validateCooldown(User $sender, User $receiver, Event $event): void
    {
        $ignoredCount = Ping::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('event_id', $event->id)
            ->where('status', 'ignored')
            ->count();

        if ($ignoredCount >= self::IGNORE_COOLDOWN) {
            throw new CooldownException;
        }
    }

    private function createConnection(User $a, User $b, Event $event): Connection
    {
        $pivotA = $a->events()->where('event_id', $event->id)->first()?->pivot;
        $pivotB = $b->events()->where('event_id', $event->id)->first()?->pivot;

        $isCrossWorld = $pivotA && $pivotB && $pivotA->participant_type !== $pivotB->participant_type;

        return Connection::create([
            'user_a_id' => $a->id,
            'user_b_id' => $b->id,
            'event_id' => $event->id,
            'context' => 'Mutual ping',
            'is_cross_world' => $isCrossWorld,
        ]);
    }
}
