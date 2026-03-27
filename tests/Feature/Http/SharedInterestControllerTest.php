<?php

use App\Models\Event;
use App\Models\SharedInterest;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('creates a shared interest with a 30-minute expiry', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.share-interest', $this->event), [
            'topic' => 'AI in healthcare',
        ]);

    $response->assertOk();

    $interest = SharedInterest::first();

    expect($interest)->not->toBeNull()
        ->and($interest->topic)->toBe('AI in healthcare')
        ->and((int) abs($interest->expires_at->diffInMinutes(now())))->toBeGreaterThanOrEqual(29);
});

it('requires a topic', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.share-interest', $this->event), [
            'topic' => '',
        ]);

    $response->assertSessionHasErrors('topic');
});

it('lists only active shared interests', function () {
    SharedInterest::factory()->create([
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'topic' => 'Active topic',
        'expires_at' => now()->addMinutes(10),
    ]);

    SharedInterest::factory()->create([
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'topic' => 'Expired topic',
        'expires_at' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('event.shared-interests', $this->event));

    $response->assertOk();

    $interests = $response->json('interests');

    expect($interests)->toHaveCount(1)
        ->and($interests[0]['topic'])->toBe('Active topic');
});

it('requires authentication', function () {
    $response = $this->post(route('event.share-interest', $this->event), [
        'topic' => 'Test',
    ]);

    $response->assertRedirect();
});
