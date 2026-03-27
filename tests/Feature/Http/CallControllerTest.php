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
});

it('initiates a 3-minute call', function () {
    $response = $this->actingAs($this->userA)
        ->post(route('connection.call.start', $this->connection));

    $response->assertOk()
        ->assertJsonStructure(['call_id', 'room_id', 'expires_at']);
});

it('rejects non-participants from starting a call', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->post(route('connection.call.start', $this->connection));

    $response->assertForbidden();
});

it('extends a call by 3 minutes', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(3),
        'extensions' => 0,
    ]);

    $response = $this->actingAs($this->userA)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertOk();

    expect($call->fresh()->extensions)->toBe(1);
});

it('rejects call extensions after the expiry boundary', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'expired-room',
        'started_at' => now()->subMinutes(10),
        'expires_at' => now()->subMinute(),
        'extensions' => 0,
    ]);

    $response = $this->actingAs($this->userA)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertUnprocessable();
});

it('prevents more than 2 extensions', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(9),
        'extensions' => 2,
    ]);

    $response = $this->actingAs($this->userA)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Maximum extensions reached');
});

it('rejects strangers from extending a call', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(3),
        'extensions' => 0,
    ]);

    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertForbidden();
});
