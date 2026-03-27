<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();

    $this->event->participants()->attach($this->userA, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($this->userB, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->connection = Connection::factory()->create([
        'user_a_id' => $this->userA->id,
        'user_b_id' => $this->userB->id,
        'event_id' => $this->event->id,
    ]);
});

it('sends a message in a connection', function () {
    $response = $this->actingAs($this->userA)
        ->post(route('connection.messages.store', $this->connection), [
            'body' => 'Hello, nice to meet you!',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Sent');

    expect(Message::count())->toBe(1);
});

it('rejects empty messages', function () {
    $response = $this->actingAs($this->userA)
        ->postJson(route('connection.messages.store', $this->connection), [
            'body' => '',
        ]);

    $response->assertUnprocessable();
});

it('rejects messages over 500 characters', function () {
    $response = $this->actingAs($this->userA)
        ->postJson(route('connection.messages.store', $this->connection), [
            'body' => str_repeat('a', 501),
        ]);

    $response->assertUnprocessable();
});

it('returns message history in send order without leaking extra fields', function () {
    Message::factory()->create([
        'connection_id' => $this->connection->id,
        'sender_id' => $this->userA->id,
        'body' => 'First',
    ]);
    Message::factory()->create([
        'connection_id' => $this->connection->id,
        'sender_id' => $this->userB->id,
        'body' => 'Second',
    ]);
    Message::factory()->create([
        'connection_id' => $this->connection->id,
        'sender_id' => $this->userA->id,
        'body' => 'Third',
    ]);

    $response = $this->actingAs($this->userA)
        ->get(route('connection.messages.index', $this->connection));

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'sender_id',
                    'sender_name',
                    'body',
                    'created_at',
                ],
            ],
        ]);
});

it('prevents non-connection users from sending messages', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->post(route('connection.messages.store', $this->connection), [
            'body' => 'Hello',
        ]);

    $response->assertForbidden();
});

it('prevents non-connection users from reading the message history', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->get(route('connection.messages.index', $this->connection));

    $response->assertForbidden();
});
