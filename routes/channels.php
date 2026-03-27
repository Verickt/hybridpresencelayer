<?php

use App\Models\SessionCheckIn;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('event.{eventId}.presence', function ($user, int $eventId) {
    return $user->events()->where('event_id', $eventId)->exists();
});

Broadcast::channel('user.{userId}.notifications', function ($user, int $userId) {
    return $user->id === $userId;
});

Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    return SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $sessionId)
        ->whereNull('checked_out_at')
        ->exists();
});
