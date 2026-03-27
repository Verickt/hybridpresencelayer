<?php

use App\Models\Event;
use App\Models\User;

it('lets the organizer open the dashboard without browser errors', function () {
    $organizer = User::factory()->create([
        'name' => 'Event Organizer',
    ]);
    $event = Event::factory()->live()->create([
        'organizer_id' => $organizer->id,
        'name' => 'Organizer Summit',
    ]);

    $this->actingAs($organizer);

    visit(route('event.dashboard', $event, absolute: false))
        ->assertSee('Organizer Summit')
        ->assertNoSmoke();
});

it('shows a forbidden page when a non-organizer opens the dashboard', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create([
        'organizer_id' => $organizer->id,
    ]);
    $visitor = User::factory()->create();

    $this->actingAs($visitor);

    visit(route('event.dashboard', $event, absolute: false))
        ->assertSee('403');
});
