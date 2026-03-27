<?php

use App\Exceptions\BlockedUserException;
use App\Models\Block;
use App\Models\Event;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\PingService;

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
});

it('prevents pinging a user who blocked the sender', function () {
    Block::factory()->create([
        'blocker_id' => $this->userB->id,
        'blocked_id' => $this->userA->id,
        'event_id' => $this->event->id,
    ]);

    app(PingService::class)->send($this->userA, $this->userB, $this->event);
})->throws(BlockedUserException::class);

it('excludes blocked users from the presence feed', function () {
    Block::factory()->create([
        'blocker_id' => $this->userA->id,
        'blocked_id' => $this->userB->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->userA)
        ->get(route('event.feed', $this->event));

    $response->assertOk();

    $participantIds = collect($response->original->getData()['page']['props']['participants'])
        ->pluck('id');

    expect($participantIds)->not->toContain($this->userB->id);
});

it('excludes blocked users from matching results', function () {
    Block::factory()->create([
        'blocker_id' => $this->userA->id,
        'blocked_id' => $this->userB->id,
        'event_id' => $this->event->id,
    ]);

    $matches = app(MatchingService::class)->topMatches($this->userA, $this->event);

    $matchedIds = $matches->pluck('user')->pluck('id');

    expect($matchedIds)->not->toContain($this->userB->id);
});
