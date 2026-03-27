<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;

it('lets the organizer open the room qr display page from the session detail', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create([
        'organizer_id' => $organizer->id,
    ]);
    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
        'title' => 'Zero Trust Keynote',
    ]);

    $this->actingAs($organizer);

    visit(route('event.sessions.show', [$event, $session], absolute: false))
        ->assertSee('Zero Trust Keynote')
        ->click('Show room QR')
        ->assertPathIs("/event/{$event->slug}/sessions/{$session->id}/qr-display")
        ->assertSee('Room QR')
        ->assertSee('Copy remote join link')
        ->assertNoSmoke();
});
