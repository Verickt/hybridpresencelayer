<?php

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
});

it('renders the chat page for a connection member', function () {
    $response = $this->actingAs($this->userA)
        ->get(route('event.connection.chat', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
        ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Chat')
            ->has('event')
            ->has('connection')
            ->has('peer')
        );
});

it('renders the chat page for the other connection member', function () {
    $response = $this->actingAs($this->userB)
        ->get(route('event.connection.chat', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
        ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Chat')
        );
});

it('forbids non-connection users from accessing the chat page', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->get(route('event.connection.chat', [
            'event' => $this->event->slug,
            'connection' => $this->connection->id,
        ]));

    $response->assertForbidden();
});

it('requires authentication', function () {
    $response = $this->get(route('event.connection.chat', [
        'event' => $this->event->slug,
        'connection' => $this->connection->id,
    ]));

    $response->assertRedirect();
});
