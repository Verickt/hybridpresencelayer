<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\Suggestion;
use App\Models\User;

it('renders post-session connections page with session affinity suggestions', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    $suggestedUser = User::factory()->create();
    Suggestion::create([
        'suggested_to_id' => $user->id,
        'suggested_user_id' => $suggestedUser->id,
        'event_id' => $event->id,
        'score' => 0.85,
        'reason' => 'You both vibed during "Keynote"',
        'trigger' => 'session_affinity',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user)->get(
        route('event.sessions.post-session', [$event, $session])
    );

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Event/PostSessionConnections')
        ->has('suggestions', 1)
    );
});

it('returns 404 for session that ended more than 15 minutes ago', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(30),
    ]);

    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(30)]);

    $response = $this->actingAs($user)->get(
        route('event.sessions.post-session', [$event, $session])
    );

    $response->assertNotFound();
});
