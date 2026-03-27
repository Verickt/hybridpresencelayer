<?php

use App\Models\Booth;
use App\Models\Connection;
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

Broadcast::channel('connection.{connectionId}.chat', function ($user, int $connectionId) {
    $connection = Connection::find($connectionId);

    return $connection && ($connection->user_a_id === $user->id || $connection->user_b_id === $user->id);
});

Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    return SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $sessionId)
        ->whereNull('checked_out_at')
        ->exists();
});

Broadcast::channel('booth.{boothId}', function ($user, int $boothId) {
    $booth = Booth::find($boothId);

    return $booth && $user->events()->where('event_id', $booth->event_id)->exists();
});
