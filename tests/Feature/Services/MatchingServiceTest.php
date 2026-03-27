<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\InterestTag;
use App\Models\SessionCheckIn;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\MatchingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(MatchingService::class);
});

it('scores higher when users share more tags', function () {
    $tagA = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $tagB = InterestTag::factory()->create(['name' => 'DevOps']);
    $tagC = InterestTag::factory()->create(['name' => 'AI/ML']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    $userA->interestTags()->attach([$tagA->id, $tagB->id], ['event_id' => $this->event->id]);
    $userB->interestTags()->attach([$tagA->id, $tagB->id], ['event_id' => $this->event->id]);
    $userC->interestTags()->attach([$tagC->id], ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($userC, ['participant_type' => 'remote', 'status' => 'available']);

    $scoreAB = $this->service->score($userA, $userB, $this->event);
    $scoreAC = $this->service->score($userA, $userC, $this->event);

    expect($scoreAB)->toBeGreaterThan($scoreAC);
});

it('scores higher when both users are in the same session', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, [
        'participant_type' => 'physical',
        'status' => 'in_session',
        'context_badge' => "Watching: {$session->title}",
    ]);
    $this->event->participants()->attach($userB, [
        'participant_type' => 'remote',
        'status' => 'in_session',
        'context_badge' => "Watching: {$session->title}",
    ]);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id]);

    $withSession = $this->service->score($userA, $userB, $this->event);

    SessionCheckIn::truncate();

    $withoutSession = $this->service->score($userA, $userB, $this->event);

    expect($withSession)->toBeGreaterThan($withoutSession);
});

it('scores lower when a user is busy', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    $availableScore = $this->service->score($userA, $userB, $this->event);

    $userB->events()->updateExistingPivot($this->event->id, ['status' => 'busy']);

    $busyScore = $this->service->score($userA, $userB, $this->event);

    expect($availableScore)->toBeGreaterThan($busyScore);
});

it('excludes invisible participants and recently declined suggestions from top matches', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $eligible = User::factory()->create();
    $invisible = User::factory()->create(['is_invisible' => true]);
    $recentlyDeclined = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $eligible->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $invisible->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $recentlyDeclined->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($eligible, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($invisible, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($recentlyDeclined, ['participant_type' => 'remote', 'status' => 'available']);

    Suggestion::factory()->create([
        'suggested_to_id' => $userA->id,
        'suggested_user_id' => $recentlyDeclined->id,
        'event_id' => $this->event->id,
        'status' => 'declined',
        'updated_at' => now(),
    ]);

    $matches = $this->service->topMatches($userA, $this->event, limit: 5);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['user']->id)->toBe($eligible->id);
});

it('returns an empty collection when no eligible matches exist', function () {
    $user = User::factory()->create();
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    $matches = $this->service->topMatches($user, $this->event, limit: 3);

    expect($matches)->toHaveCount(0);
});
