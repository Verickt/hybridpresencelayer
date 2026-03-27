<?php

use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

it('has a sender and receiver', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $event = Event::factory()->create();

    $ping = Ping::factory()->create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'event_id' => $event->id,
    ]);

    expect($ping->sender->id)->toBe($sender->id)
        ->and($ping->receiver->id)->toBe($receiver->id)
        ->and($ping->event->id)->toBe($event->id);
});

it('expires after 30 minutes', function () {
    $fresh = Ping::factory()->create(['created_at' => now()]);
    $expired = Ping::factory()->create(['created_at' => now()->subMinutes(31)]);

    expect($fresh->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('scopes to non-expired pings', function () {
    Ping::factory()->create(['created_at' => now(), 'status' => 'pending']);
    Ping::factory()->create(['created_at' => now()->subMinutes(31), 'status' => 'pending']);
    Ping::factory()->create(['created_at' => now(), 'status' => 'ignored']);

    expect(Ping::active()->count())->toBe(1);
});

it('defaults to pending status', function () {
    $ping = Ping::factory()->create();

    expect($ping->status)->toBe('pending');
});
