<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('event.{eventId}.presence', function ($user, int $eventId) {
    return $user->events()->where('event_id', $eventId)->exists();
});
