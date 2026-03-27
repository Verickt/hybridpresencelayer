<?php

use App\Models\Block;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->target = User::factory()->create();

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($this->target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
});

it('blocks a user within an event', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.block', [$this->event, $this->target]));

    $response->assertOk();

    expect(Block::count())->toBe(1)
        ->and(Block::first()->blocker_id)->toBe($this->user->id)
        ->and(Block::first()->blocked_id)->toBe($this->target->id);
});

it('does not create duplicate blocks', function () {
    Block::factory()->create([
        'blocker_id' => $this->user->id,
        'blocked_id' => $this->target->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('event.block', [$this->event, $this->target]));

    $response->assertOk();
    expect(Block::count())->toBe(1);
});

it('unblocks a user', function () {
    Block::factory()->create([
        'blocker_id' => $this->user->id,
        'blocked_id' => $this->target->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('event.unblock', [$this->event, $this->target]));

    $response->assertOk();
    expect(Block::count())->toBe(0);
});

it('requires authentication to block', function () {
    $response = $this->post(route('event.block', [$this->event, $this->target]));

    $response->assertRedirect();
});
