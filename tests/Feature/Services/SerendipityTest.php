<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;
use App\Services\MatchingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(MatchingService::class);
});

it('returns a serendipity match with zero tag overlap', function () {
    $tagA = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $tagB = InterestTag::factory()->create(['name' => 'AI/ML']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tagA, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tagB, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    $match = $this->service->serendipityMatch($userA, $this->event);

    expect($match)->not->toBeNull()
        ->and($match->id)->toBe($userB->id);
});

it('skips busy candidates in serendipity mode', function () {
    $tagA = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $tagB = InterestTag::factory()->create(['name' => 'AI/ML']);

    $userA = User::factory()->create();
    $busyCandidate = User::factory()->create();

    $userA->interestTags()->attach($tagA, ['event_id' => $this->event->id]);
    $busyCandidate->interestTags()->attach($tagB, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($busyCandidate, ['participant_type' => 'remote', 'status' => 'busy']);

    $match = $this->service->serendipityMatch($userA, $this->event);

    expect($match)->toBeNull();
});
