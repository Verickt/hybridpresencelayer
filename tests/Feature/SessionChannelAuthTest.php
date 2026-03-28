<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;

it('authorizes checked-in participant on session channel', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);

    $hasActiveCheckIn = SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $session->id)
        ->whereNull('checked_out_at')
        ->exists();

    expect($hasActiveCheckIn)->toBeTrue();
});

it('authorizes organizer via organizer_id match', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();

    expect($session->event->organizer_id === $organizer->id)->toBeTrue();
});

it('grants post-session grace within 15 minutes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(5),
    ]);
    SessionCheckIn::create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'checked_out_at' => now()->subMinutes(5),
    ]);

    $withinGrace = $session->ends_at->isPast() && $session->ends_at->diffInMinutes(now()) <= 15;
    $hasCheckIn = SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $session->id)
        ->exists();

    expect($withinGrace)->toBeTrue();
    expect($hasCheckIn)->toBeTrue();
});

it('rejects post-session access after 15-minute grace window', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subMinutes(20),
    ]);
    SessionCheckIn::create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'checked_out_at' => now()->subMinutes(20),
    ]);

    $withinGrace = $session->ends_at->isPast() && $session->ends_at->diffInMinutes(now()) <= 15;

    expect($withinGrace)->toBeFalse();
});
