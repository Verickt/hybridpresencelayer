<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\User;

it('connects two users in an event', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $event = Event::factory()->create();

    $connection = Connection::factory()->create([
        'user_a_id' => $userA->id,
        'user_b_id' => $userB->id,
        'event_id' => $event->id,
        'context' => 'Matched during Zero Trust keynote',
    ]);

    expect($connection->userA->id)->toBe($userA->id)
        ->and($connection->userB->id)->toBe($userB->id)
        ->and($connection->context)->toBe('Matched during Zero Trust keynote');
});

it('tracks cross-world connections', function () {
    $connection = Connection::factory()->create(['is_cross_world' => true]);

    expect($connection->is_cross_world)->toBeTrue();
});

it('stores the lower user id first for uniqueness', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $lowId = min($first->id, $second->id);
    $highId = max($first->id, $second->id);

    $connection = Connection::factory()->create([
        'user_a_id' => $highId,
        'user_b_id' => $lowId,
    ]);

    expect($connection->fresh()->user_a_id)->toBe($lowId)
        ->and($connection->fresh()->user_b_id)->toBe($highId);
});
