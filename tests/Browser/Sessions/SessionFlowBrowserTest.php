<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;

it('lets a participant check into a session from the session page', function () {
    $event = Event::factory()->live()->create();
    $participant = User::factory()->create();

    $event->participants()->attach($participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
        'qa_enabled' => true,
    ]);

    $this->actingAs($participant);

    visit(route('event.sessions.show', [$event, $session], absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee($session->title)
        ->click('Join session')
        ->assertSee('In Session')
        ->assertNoSmoke();
});

it('redirects guests to login when they open the session page', function () {
    $event = Event::factory()->live()->create();
    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
    ]);

    visit(route('event.sessions.show', [$event, $session], absolute: false))
        ->on()->iPhone14Pro()
        ->assertPathIs('/login');
});
