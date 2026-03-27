<?php

use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->sender = User::factory()->create();
    $this->receiver = User::factory()->create();

    $this->event->participants()->attach($this->sender, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($this->receiver, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
});

it('sends a ping and returns the pending status', function () {
    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->receiver]));

    $response->assertOk()
        ->assertJsonPath('status', 'pending');

    expect(Ping::count())->toBe(1);
});

it('rejects self-pings', function () {
    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->sender]));

    $response->assertUnprocessable();
});

it('returns 429 when rate limited', function () {
    Ping::factory(10)->create([
        'sender_id' => $this->sender->id,
        'event_id' => $this->event->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->receiver]));

    $response->assertStatus(429);
});

it('returns 409 for duplicate active pings', function () {
    Ping::factory()->create([
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->receiver]));

    $response->assertStatus(409);
});

it('ignores a ping only when the receiver makes the request', function () {
    $ping = Ping::factory()->create([
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->receiver)
        ->patch(route('event.ping.ignore', [$this->event, $ping]));

    $response->assertOk();

    expect($ping->fresh()->status)->toBe('ignored');
});

it('forbids strangers from ignoring a ping', function () {
    $ping = Ping::factory()->create([
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
    ]);

    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->patch(route('event.ping.ignore', [$this->event, $ping]));

    $response->assertForbidden();
});
