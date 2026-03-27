<?php

use App\Exceptions\CooldownException;
use App\Exceptions\DuplicatePingException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use App\Services\PingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(PingService::class);
});

it('creates a pending ping between event participants', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($receiver, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $ping = $this->service->send($sender, $receiver, $this->event);

    expect($ping)
        ->toBeInstanceOf(Ping::class)
        ->and($ping->sender_id)->toBe($sender->id)
        ->and($ping->receiver_id)->toBe($receiver->id)
        ->and($ping->status)->toBe('pending');
});

it('rejects self-pings', function () {
    $user = User::factory()->create();

    $this->event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->service->send($user, $user, $this->event);
})->throws(InvalidArgumentException::class);

it('detects mutual match and creates a single connection', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->event->participants()->attach($userA, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($userB, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->service->send($userA, $userB, $this->event);

    $ping = $this->service->send($userB, $userA, $this->event);

    expect($ping->status)->toBe('matched')
        ->and(Connection::where('event_id', $this->event->id)->count())->toBe(1)
        ->and(Ping::where('status', 'matched')->count())->toBe(2);
});

it('marks cross-world connections when the pair is physical and remote', function () {
    $physical = User::factory()->create();
    $remote = User::factory()->create();

    $this->event->participants()->attach($physical, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($remote, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->service->send($physical, $remote, $this->event);
    $this->service->send($remote, $physical, $this->event);

    $connection = Connection::firstOrFail();

    expect($connection->is_cross_world)->toBeTrue();
});

it('rate limits to 10 pings per hour', function () {
    $sender = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $receivers = User::factory(11)->create();

    foreach ($receivers as $receiver) {
        $this->event->participants()->attach($receiver, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    for ($index = 0; $index < 10; $index++) {
        $this->service->send($sender, $receivers[$index], $this->event);
    }

    $this->service->send($sender, $receivers[10], $this->event);
})->throws(RateLimitExceededException::class);

it('ignores stale pings when evaluating the hourly limit', function () {
    $sender = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $receivers = User::factory(11)->create();

    foreach ($receivers as $receiver) {
        $this->event->participants()->attach($receiver, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    foreach (range(0, 9) as $index) {
        Ping::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receivers[$index]->id,
            'event_id' => $this->event->id,
            'status' => 'ignored',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
    }

    $ping = $this->service->send($sender, $receivers[10], $this->event);

    expect($ping)->toBeInstanceOf(Ping::class)
        ->and($ping->status)->toBe('pending');
});

it('allows sending pings again after the hourly window passes', function () {
    $sender = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $receivers = User::factory(12)->create();

    foreach ($receivers as $receiver) {
        $this->event->participants()->attach($receiver, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    for ($index = 0; $index < 10; $index++) {
        $this->service->send($sender, $receivers[$index], $this->event);
    }

    expect(fn () => $this->service->send($sender, $receivers[10], $this->event))
        ->toThrow(RateLimitExceededException::class);

    $this->travel(61)->minutes();

    $ping = $this->service->send($sender, $receivers[11], $this->event);

    expect($ping->status)->toBe('pending');
});

it('rejects duplicate active pings', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($receiver, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->service->send($sender, $receiver, $this->event);
    $this->service->send($sender, $receiver, $this->event);
})->throws(DuplicatePingException::class);

it('respects the ignore cooldown after three ignored pings', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $this->event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($receiver, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    for ($index = 0; $index < 3; $index++) {
        Ping::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'event_id' => $this->event->id,
            'status' => 'ignored',
        ]);
    }

    $this->service->send($sender, $receiver, $this->event);
})->throws(CooldownException::class);
