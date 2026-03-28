<?php

use App\Events\SessionEnded;
use App\Jobs\SessionEndedJob;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionReaction;
use App\Models\User;
use App\Services\SessionEngagementService;
use Illuminate\Support\Facades\Event as EventFacade;

it('computes reaction sync score for users who reacted in same time windows', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    // Both react in same 30-sec window
    $baseTime = now()->subMinutes(30);
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime]);
    SessionReaction::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'type' => 'clap', 'created_at' => $baseTime->copy()->addSeconds(10)]);

    // User A reacts alone in another window
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'think', 'created_at' => $baseTime->copy()->addMinutes(5)]);

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $edge = SessionEngagementEdge::where('event_session_id', $session->id)
        ->where('user_a_id', min($userA->id, $userB->id))
        ->where('user_b_id', max($userA->id, $userB->id))
        ->first();

    expect($edge)->not->toBeNull();
    expect($edge->reaction_sync_score)->toBeGreaterThan(0.0);
    expect($edge->reaction_sync_score)->toBeLessThanOrEqual(1.0);
});

it('computes qa interaction score for users who replied to each other', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $userA->id,
    ]);
    SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $userB->id,
        'body' => 'Great point!',
    ]);

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $edge = SessionEngagementEdge::where('event_session_id', $session->id)
        ->where('user_a_id', min($userA->id, $userB->id))
        ->where('user_b_id', max($userA->id, $userB->id))
        ->first();

    expect($edge)->not->toBeNull();
    expect($edge->qa_interaction_score)->toBeGreaterThan(0.0);
});

it('creates no edges when users have no shared engagement', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $count = SessionEngagementEdge::where('event_session_id', $session->id)->count();
    expect($count)->toBe(0);
});

it('auto-checks-out remaining participants and computes engagement edges on session end', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(1),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id]);

    $baseTime = now()->subMinutes(30);
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime]);
    SessionReaction::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime->copy()->addSeconds(5)]);

    EventFacade::fake([SessionEnded::class]);

    (new SessionEndedJob($session))->handle(
        app(SessionEngagementService::class),
    );

    expect(SessionCheckIn::where('event_session_id', $session->id)->whereNull('checked_out_at')->count())->toBe(0);
    expect(SessionEngagementEdge::where('event_session_id', $session->id)->count())->toBe(1);
    EventFacade::assertDispatched(SessionEnded::class);
});
