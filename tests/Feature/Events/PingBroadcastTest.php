<?php

use App\Events\MutualMatchCreated;
use App\Events\PingReceived;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

it('broadcasts ping on the receiver private channel with a safe payload', function () {
    $event = Event::factory()->live()->create();
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $ping = Ping::factory()->create([
        'event_id' => $event->id,
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
    ]);

    $broadcastEvent = new PingReceived($event, $ping);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-user.{$receiver->id}.notifications")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'ping_id' => $ping->id,
            'event_id' => $event->id,
        ]);

    expect($broadcastEvent->broadcastWith()['sender'])->toMatchArray([
        'id' => $sender->id,
        'name' => $sender->name,
    ]);
});

it('broadcasts mutual match to both users with connection context', function () {
    $event = Event::factory()->live()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $connection = Connection::factory()->create([
        'user_a_id' => $userA->id,
        'user_b_id' => $userB->id,
        'event_id' => $event->id,
    ]);

    $broadcastEvent = new MutualMatchCreated($event, $connection, $userA, $userB);
    $channels = $broadcastEvent->broadcastOn();

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($channels)->toHaveCount(2)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[1])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe("private-user.{$userA->id}.notifications")
        ->and($channels[1]->name)->toBe("private-user.{$userB->id}.notifications")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'connection_id' => $connection->id,
            'event_id' => $event->id,
        ]);

    expect($broadcastEvent->broadcastWith()['user_a'])->toMatchArray([
        'id' => $userA->id,
        'name' => $userA->name,
    ]);

    expect($broadcastEvent->broadcastWith()['user_b'])->toMatchArray([
        'id' => $userB->id,
        'name' => $userB->name,
    ]);
});
