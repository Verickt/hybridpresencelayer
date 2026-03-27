<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Notifications\InAppNotification;

class NotificationService
{
    /** @var array<string, array{per_hour: int, per_day: int}> */
    private const FREQUENCY_LIMITS = [
        'high' => ['per_hour' => PHP_INT_MAX, 'per_day' => PHP_INT_MAX],
        'medium' => ['per_hour' => 4, 'per_day' => 20],
        'low' => ['per_hour' => 2, 'per_day' => 10],
    ];

    public function send(User $user, Event $event, string $type, string $priority, string $message, array $data = []): bool
    {
        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;

        if (! $pivot) {
            return false;
        }

        // DND blocks everything
        if ($pivot->notification_mode === 'dnd') {
            return false;
        }

        // Quiet mode only allows high priority
        if ($pivot->notification_mode === 'quiet' && $priority !== 'high') {
            return false;
        }

        // Busy status blocks non-high priority
        if ($pivot->status === 'busy' && $priority !== 'high') {
            return false;
        }

        // Check frequency limits
        if (! $this->withinFrequencyLimit($user, $priority)) {
            return false;
        }

        // Deduplication check
        if ($this->isDuplicate($user, $event, $type, $message, $data)) {
            return false;
        }

        // Store as database notification
        $user->notify(new InAppNotification(
            type: $type,
            priority: $priority,
            message: $message,
            eventId: $event->id,
            data: $data,
        ));

        return true;
    }

    private function withinFrequencyLimit(User $user, string $priority): bool
    {
        $limits = self::FREQUENCY_LIMITS[$priority] ?? self::FREQUENCY_LIMITS['low'];

        $hourCount = $user->notifications()
            ->where('created_at', '>', now()->subHour())
            ->whereJsonContains('data->priority', $priority)
            ->count();

        if ($hourCount >= $limits['per_hour']) {
            return false;
        }

        $dayCount = $user->notifications()
            ->where('created_at', '>', now()->subDay())
            ->whereJsonContains('data->priority', $priority)
            ->count();

        return $dayCount < $limits['per_day'];
    }

    private function isDuplicate(User $user, Event $event, string $type, string $message, array $data): bool
    {
        return $user->unreadNotifications()
            ->whereJsonContains('data->type', $type)
            ->whereJsonContains('data->message', $message)
            ->whereJsonContains('data->event_id', $event->id)
            ->when(! empty($data), function ($query) use ($data) {
                foreach ($data as $key => $value) {
                    $query->whereJsonContains("data->{$key}", $value);
                }
            })
            ->exists();
    }
}
