<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the organizer open the session qr display page', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create([
        'organizer_id' => $organizer->id,
    ]);
    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
        'title' => 'Zero Trust Keynote',
    ]);

    $response = $this->actingAs($organizer)
        ->get(route('event.sessions.qr-display', [$event, $session]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/SessionQrDisplay')
            ->where('event.slug', $event->slug)
            ->where('session.title', 'Zero Trust Keynote')
            ->where('qr.remote_join_url', route('event.sessions.show', [$event, $session]))
            ->where('qr.svg', fn (string $svg) => str_contains($svg, '<svg'))
            ->where('qr.payload', fn (string $payload) => str_starts_with($payload, "/event/{$event->slug}/sessions/{$session->id}/qr-checkin?"))
            ->etc()
        );
});

it('forbids non-organizers from the session qr display page', function () {
    $organizer = User::factory()->create();
    $participant = User::factory()->create();
    $event = Event::factory()->live()->create([
        'organizer_id' => $organizer->id,
    ]);
    $event->participants()->attach($participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
    ]);

    $this->actingAs($participant)
        ->get(route('event.sessions.qr-display', [$event, $session]))
        ->assertForbidden();
});
