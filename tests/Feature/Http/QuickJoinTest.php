<?php

use App\Models\Event;
use App\Models\User;

it('shows the quick join form for an event', function () {
    $event = Event::factory()->live()->create();

    $this->get(route('event.join', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/QuickJoin')
            ->where('event.slug', $event->slug)
        );
});

it('creates a user with name only and logs them in', function () {
    $event = Event::factory()->live()->create();

    $response = $this->post(route('event.join.store', $event), [
        'name' => 'Taylor Brooks',
    ]);

    $user = User::where('name', 'Taylor Brooks')->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBeNull()
        ->and($user->password)->toBeNull();

    expect($user->events->pluck('id'))->toContain($event->id);

    $response->assertRedirect(route('event.onboarding.type', $event));

    $this->assertAuthenticatedAs($user);
});

it('validates name is required', function () {
    $event = Event::factory()->live()->create();

    $this->post(route('event.join.store', $event), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('redirects authenticated users who already joined to feed', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->actingAs($user)
        ->get(route('event.join', $event))
        ->assertRedirect(route('event.feed', $event));
});

it('attaches authenticated user without event and redirects to onboarding', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('event.join', $event))
        ->assertRedirect(route('event.onboarding.type', $event));

    expect($user->events->pluck('id'))->toContain($event->id);
});
