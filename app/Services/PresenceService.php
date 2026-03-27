<?php

namespace App\Services;

use App\Events\PresenceStateChanged;
use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class PresenceService
{
    private const VALID_STATUSES = ['available', 'in_session', 'at_booth', 'busy', 'away'];

    public function updateStatus(User $user, Event $event, string $status): void
    {
        if (! in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        if (! $user->events()->where('event_id', $event->id)->exists()) {
            throw new AuthorizationException('User is not a participant of this event.');
        }

        $previousStatus = $user->events()->where('event_id', $event->id)->first()->pivot->status;

        $user->events()->updateExistingPivot($event->id, [
            'status' => $status,
            'last_active_at' => now(),
        ]);

        if ($status !== $previousStatus) {
            PresenceStateChanged::dispatch($event, $user, $status);
        }
    }

    public function checkInToSession(User $user, Event $event, EventSession $session): void
    {
        $this->ensureParticipant($user, $event);

        $this->checkOutOfSession($user, $event);
        BoothVisit::where('user_id', $user->id)->whereNull('left_at')->update(['left_at' => now()]);

        SessionCheckIn::updateOrCreate(
            ['user_id' => $user->id, 'event_session_id' => $session->id],
            ['checked_out_at' => null]
        );

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'in_session',
            'context_badge' => "In session: {$session->title}",
            'last_active_at' => now(),
        ]);

        PresenceStateChanged::dispatch($event, $user, 'in_session', "In session: {$session->title}");
    }

    public function checkOutOfSession(User $user, Event $event): void
    {
        $this->ensureParticipant($user, $event);

        SessionCheckIn::where('user_id', $user->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'available',
            'context_badge' => null,
            'last_active_at' => now(),
        ]);

        PresenceStateChanged::dispatch($event, $user, 'available');
    }

    public function checkInToBooth(User $user, Event $event, Booth $booth): void
    {
        $this->ensureParticipant($user, $event);

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'at_booth',
            'context_badge' => "At Booth: {$booth->name}",
            'last_active_at' => now(),
        ]);

        PresenceStateChanged::dispatch($event, $user, 'at_booth', "At Booth: {$booth->name}");
    }

    public function markInactive(User $user, Event $event): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'status' => 'away',
        ]);

        PresenceStateChanged::dispatch($event, $user, 'away');
    }

    public function touch(User $user, Event $event): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'last_active_at' => now(),
        ]);
    }

    public function toggleInvisible(User $user): void
    {
        $user->update(['is_invisible' => ! $user->is_invisible]);
    }

    private function ensureParticipant(User $user, Event $event): void
    {
        if (! $user->events()->where('event_id', $event->id)->exists()) {
            throw new AuthorizationException('User is not a participant of this event.');
        }
    }
}
