<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\SuggestionService;

it('includes session affinity in matching score', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    SessionEngagementEdge::create([
        'event_session_id' => $session->id,
        'user_a_id' => min($userA->id, $userB->id),
        'user_b_id' => max($userA->id, $userB->id),
        'reaction_sync_score' => 0.8,
        'qa_interaction_score' => 0.6,
    ]);

    $matchingService = app(MatchingService::class);
    $score = $matchingService->score($userA, $userB, $event);

    expect($score)->toBeGreaterThan(0.0);
});

it('generates session affinity suggestions after session ends', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    SessionEngagementEdge::create([
        'event_session_id' => $session->id,
        'user_a_id' => min($userA->id, $userB->id),
        'user_b_id' => max($userA->id, $userB->id),
        'reaction_sync_score' => 0.9,
        'qa_interaction_score' => 0.5,
    ]);

    $suggestionService = app(SuggestionService::class);
    $suggestions = $suggestionService->generateSessionAffinitySuggestions($userA, $event, $session);

    expect($suggestions)->not->toBeEmpty();
    expect($suggestions->first()->trigger)->toBe('session_affinity');

    $reason = $suggestions->first()->reason;
    expect(str_contains($reason, 'session') || str_contains($reason, 'vibed') || str_contains($reason, 'engaged'))->toBeTrue();
});
