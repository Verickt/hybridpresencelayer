<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create(['email' => null]);
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('shows the email collection step', function () {
    $this->actingAs($this->user)
        ->get(route('event.onboarding.email', $this->event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Onboarding/EmailCollection')
            ->where('event.slug', $this->event->slug)
            ->where('currentEmail', null)
        );
});

it('saves email to user record', function () {
    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => 'taylor@example.com',
        ])
        ->assertRedirect(route('event.onboarding.ready', $this->event));

    expect($this->user->fresh()->email)->toBe('taylor@example.com');
});

it('allows skipping email by submitting empty', function () {
    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => '',
        ])
        ->assertRedirect(route('event.onboarding.ready', $this->event));

    expect($this->user->fresh()->email)->toBeNull();
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

it('shows existing email if user already has one', function () {
    $this->user->update(['email' => 'existing@example.com']);

    $this->actingAs($this->user)
        ->get(route('event.onboarding.email', $this->event))
        ->assertInertia(fn ($page) => $page
            ->where('currentEmail', 'existing@example.com')
        );
});
