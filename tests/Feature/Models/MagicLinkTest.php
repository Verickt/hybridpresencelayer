<?php

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;

it('generates a magic link with hashed token', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $result = MagicLink::generate($user, $event);

    expect($result['token'])->toBeString()
        ->and(strlen($result['token']))->toBe(64)
        ->and($result['link']->token_hash)->toBe(hash('sha256', $result['token']))
        ->and($result['link']->user)->toBeInstanceOf(User::class)
        ->and($result['link']->event)->toBeInstanceOf(Event::class)
        ->and($result['link']->purpose)->toBe('login');
});

it('finds a link by raw token', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $result = MagicLink::generate($user, $event);
    $found = MagicLink::findByToken($result['token']);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($result['link']->id);
});

it('revokes older unused links on generation', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $first = MagicLink::generate($user, $event);
    $second = MagicLink::generate($user, $event);

    expect($first['link']->fresh()->isRevoked())->toBeTrue()
        ->and($second['link']->fresh()->isRevoked())->toBeFalse();
});

it('knows if it is expired', function () {
    $valid = MagicLink::factory()->create(['expires_at' => now()->addDay()]);
    $expired = MagicLink::factory()->create(['expires_at' => now()->subHour()]);

    expect($valid->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('knows if it has been used', function () {
    $unused = MagicLink::factory()->create();
    $used = MagicLink::factory()->create(['used_at' => now()]);

    expect($unused->isUsed())->toBeFalse()
        ->and($used->isUsed())->toBeTrue();
});

it('can be consumed', function () {
    $link = MagicLink::factory()->create();

    $link->consume();

    expect($link->fresh()->used_at)->not->toBeNull();
});

it('treats revoked links as invalid', function () {
    $link = MagicLink::factory()->create(['revoked_at' => now()]);

    expect($link->isValid())->toBeFalse();
});
