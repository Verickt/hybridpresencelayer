<?php

namespace App\Services;

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

        $user->events()->updateExistingPivot($event->id, [
            'status' => $status,
            'last_active_at' => now(),
        ]);
    }

    public function checkInToSession(User $user, Event $event, EventSession $session): void
    {
        $this->checkOutOfSession($user, $event);
        BoothVisit::where('user_id', $user->id)->whereNull('left_at')->update(['left_at' => now()]);

        SessionCheckIn::updateOrCreate(
            ['user_id' => $user->id, 'event_session_id' => $session->id],
            ['checked_out_at' => null]
        );

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'in_session',
            'context_badge' => "Watching: {$session->title}",
            'last_active_at' => now(),
        ]);
    }

    public function checkOutOfSession(User $user, Event $event): void
    {
        SessionCheckIn::where('user_id', $user->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'available',
            'context_badge' => null,
            'last_active_at' => now(),
        ]);
    }

    public function checkInToBooth(User $user, Event $event, Booth $booth): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'status' => 'at_booth',
            'context_badge' => "At Booth: {$booth->name}",
            'last_active_at' => now(),
        ]);
    }

    public function markInactive(User $user, Event $event): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'status' => 'away',
        ]);
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
}
