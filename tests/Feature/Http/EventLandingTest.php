<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated visitor sees join event page', function () {
    $event = Event::factory()->live()->create([
        'name' => 'TechConf 2026',
        'venue' => 'Berlin',
        'allow_open_registration' => true,
    ]);

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
            ->where('event.name', 'TechConf 2026')
            ->where('event.venue', 'Berlin')
            ->where('event.slug', $event->slug)
            ->has('event.starts_at')
            ->has('event.ends_at')
        );
});

test('unauthenticated visitor sees empty state when no event exists', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
            ->where('event', null)
        );
});

test('authenticated participant is redirected to event feed', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('event.feed', $event));
});

test('authenticated organizer is redirected to event dashboard', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create(['organizer_id' => $organizer->id]);

    $response = $this->actingAs($organizer)->get('/');

    $response->assertRedirect(route('event.dashboard', $event));
});

test('authenticated user with no event sees join page', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
        );
});
