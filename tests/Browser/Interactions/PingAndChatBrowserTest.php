<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\User;

it('lets a participant ping someone from the feed', function () {
    $event = Event::factory()->live()->create();
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $event->participants()->attach($sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($receiver, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->actingAs($sender);

    visit(route('event.feed', $event, absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee($receiver->name)
        ->click('@ping-button')
        ->assertNoSmoke();
});

it('shows a validation error when the chat message is empty', function () {
    $event = Event::factory()->live()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($userB, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $connection = Connection::factory()->create([
        'user_a_id' => $userA->id,
        'user_b_id' => $userB->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs($userA);

    visit(route('connection.messages.index', $connection, absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee('Send')
        ->fill('textarea[name="body"]', '')
        ->click('@send-message-button')
        ->assertSee('The body field is required.')
        ->assertNoSmoke();
});
