<?php

use App\Models\Event;
use App\Models\Suggestion;
use App\Models\User;

it('creates a suggestion between two users for an event', function () {
    $suggestion = Suggestion::factory()->create();

    expect($suggestion->suggestedTo)->toBeInstanceOf(User::class)
        ->and($suggestion->suggestedUser)->toBeInstanceOf(User::class)
        ->and($suggestion->event)->toBeInstanceOf(Event::class)
        ->and($suggestion->score)->toBeFloat()
        ->and($suggestion->status)->toBe('pending');
});

it('expires after the ttl has passed', function () {
    $fresh = Suggestion::factory()->create([
        'expires_at' => now()->addMinutes(15),
    ]);
    $expired = Suggestion::factory()->create([
        'expires_at' => now()->subMinute(),
    ]);

    expect($fresh->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('scopes to active pending suggestions only', function () {
    Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);
    Suggestion::factory()->create([
        'status' => 'accepted',
        'expires_at' => now()->addMinutes(10),
    ]);
    Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    expect(Suggestion::active()->count())->toBe(1);
});

it('scopes suggestions to a specific recipient', function () {
    $recipient = User::factory()->create();

    Suggestion::factory()->create([
        'suggested_to_id' => $recipient->id,
    ]);
    Suggestion::factory()->create();

    expect(Suggestion::forUser($recipient)->count())->toBe(1);
});

it('allows historical suggestions for the same pair when older records are no longer active', function () {
    $recipient = User::factory()->create();
    $suggestedUser = User::factory()->create();
    $event = Event::factory()->create();

    Suggestion::factory()->create([
        'suggested_to_id' => $recipient->id,
        'suggested_user_id' => $suggestedUser->id,
        'event_id' => $event->id,
        'status' => 'declined',
        'expires_at' => now()->subMinute(),
    ]);
    Suggestion::factory()->create([
        'suggested_to_id' => $recipient->id,
        'suggested_user_id' => $suggestedUser->id,
        'event_id' => $event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(15),
    ]);

    expect(
        Suggestion::where('suggested_to_id', $recipient->id)
            ->where('suggested_user_id', $suggestedUser->id)
            ->where('event_id', $event->id)
            ->count()
    )->toBe(2);
});
