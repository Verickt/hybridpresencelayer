<?php

use App\Models\Call;
use App\Models\Connection;
use App\Models\Event;
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

    $this->call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room-id',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(3),
        'extensions' => 0,
    ]);
});

it('renders the video call page for a connection member', function () {
    $response = $this->actingAs($this->userA)
        ->get(route('event.connection.call', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
            'call' => $this->call->id,
        ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/VideoCall')
            ->has('event')
            ->has('connection')
            ->has('call')
            ->has('peer')
        );
});

it('renders for the other connection member', function () {
    $response = $this->actingAs($this->userB)
        ->get(route('event.connection.call', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
            'call' => $this->call->id,
        ]));

    $response->assertOk();
});

it('forbids non-connection users', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->get(route('event.connection.call', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
            'call' => $this->call->id,
        ]));

    $response->assertForbidden();
});

it('returns 404 for a call not belonging to the connection', function () {
    $otherConnection = Connection::factory()->create(['event_id' => $this->event->id]);
    $otherCall = Call::create([
        'connection_id' => $otherConnection->id,
        'initiator_id' => $otherConnection->user_a_id,
        'room_id' => 'other-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(3),
        'extensions' => 0,
    ]);

    $response = $this->actingAs($this->userA)
        ->get(route('event.connection.call', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
            'call' => $otherCall->id,
        ]));

    $response->assertNotFound();
});

it('requires authentication', function () {
    $response = $this->get(route('event.connection.call', [
        'event' => $this->event->slug,
        'connection' => $this->connection->id,
        'call' => $this->call->id,
    ]));

    $response->assertRedirect();
});
