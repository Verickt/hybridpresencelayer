<?php

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;

it('authenticates with a valid magic link from the browser', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'i'.str_repeat('j', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
    ]);

    visit(route('magic-link.authenticate', ['token' => $rawToken], absolute: false))
        ->assertPathIs('/dashboard')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('redirects expired magic links back to login', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'k'.str_repeat('l', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->subHour(),
    ]);

    visit(route('magic-link.authenticate', ['token' => $rawToken], absolute: false))
        ->assertPathIs('/login')
        ->assertPresent('@magic-link-error')
        ->assertSeeIn('@magic-link-error', 'invalid or has expired')
        ->assertNoAccessibilityIssues();
});

it('redirects reused magic links back to login', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'm'.str_repeat('n', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    visit(route('magic-link.authenticate', ['token' => $rawToken], absolute: false))
        ->assertPathIs('/login')
        ->assertPresent('@magic-link-error')
        ->assertSeeIn('@magic-link-error', 'invalid or has expired')
        ->assertNoAccessibilityIssues();
});

it('redirects unknown magic links back to login with an accessible error state', function () {
    visit(route('magic-link.authenticate', ['token' => 'o'.str_repeat('p', 63)], absolute: false))
        ->assertPathIs('/login')
        ->assertPresent('@magic-link-error')
        ->assertSeeIn('@magic-link-error', 'invalid or has expired')
        ->assertNoAccessibilityIssues();
});
