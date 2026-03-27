<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(SuggestionService::class);
});

it('generates suggestions for a user', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    $others = User::factory(5)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions)->toHaveCount(3)
        ->and($suggestions->first())->toBeInstanceOf(Suggestion::class);
});

it('returns no suggestions when the user already has three active ones', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    Suggestion::factory(3)->create([
        'suggested_to_id' => $user->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $others = User::factory(3)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions)->toHaveCount(0);
});

it('ignores expired pending suggestions when evaluating the active suggestion cap', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    Suggestion::factory(3)->create([
        'suggested_to_id' => $user->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    $others = User::factory(3)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions)->toHaveCount(3);
});

it('declines a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['status' => 'pending']);

    $this->service->decline($suggestion);

    expect($suggestion->fresh()->status)->toBe('declined');
});

it('accepts a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['status' => 'pending']);

    $this->service->accept($suggestion);

    expect($suggestion->fresh()->status)->toBe('accepted');
});

it('does not re-suggest declined pairs within 2 hours', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    Suggestion::factory()->create([
        'suggested_to_id' => $userA->id,
        'suggested_user_id' => $userB->id,
        'event_id' => $this->event->id,
        'status' => 'declined',
        'updated_at' => now(),
    ]);

    $suggestions = $this->service->generateForUser($userA, $this->event);

    expect($suggestions->pluck('suggested_user_id'))->not->toContain($userB->id);
});

it('allows a declined pair to be suggested again after the 2-hour cooldown passes', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    Suggestion::factory()->create([
        'suggested_to_id' => $userA->id,
        'suggested_user_id' => $userB->id,
        'event_id' => $this->event->id,
        'status' => 'declined',
        'updated_at' => now()->subHours(3),
    ]);

    $suggestions = $this->service->generateForUser($userA, $this->event);

    expect($suggestions->pluck('suggested_user_id'))->toContain($userB->id);
});

it('does not generate a duplicate pending suggestion for the same pair', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();
    $candidate = User::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $candidate->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($candidate, ['participant_type' => 'remote', 'status' => 'available']);

    Suggestion::factory()->create([
        'suggested_to_id' => $user->id,
        'suggested_user_id' => $candidate->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(15),
    ]);

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions->pluck('suggested_user_id'))->not->toContain($candidate->id)
        ->and(Suggestion::query()->where('suggested_to_id', $user->id)->where('suggested_user_id', $candidate->id)->count())->toBe(1);
});

it('skips invisible and already connected users', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();
    $eligible = User::factory()->create();
    $invisible = User::factory()->create(['is_invisible' => true]);
    $connected = User::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $eligible->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $invisible->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $connected->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($eligible, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($invisible, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($connected, ['participant_type' => 'remote', 'status' => 'available']);

    Connection::factory()->create([
        'event_id' => $this->event->id,
        'user_a_id' => $user->id,
        'user_b_id' => $connected->id,
    ]);

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions->pluck('suggested_user_id'))->toContain($eligible->id)
        ->and($suggestions->pluck('suggested_user_id'))->not->toContain($invisible->id)
        ->and($suggestions->pluck('suggested_user_id'))->not->toContain($connected->id);
});
