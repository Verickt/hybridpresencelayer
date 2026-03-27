<?php

use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\SessionReaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

it('tracks session check-ins', function () {
    $user = User::factory()->create();
    $session = EventSession::factory()->create();

    $checkIn = SessionCheckIn::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
    ]);

    expect($checkIn->user->id)->toBe($user->id)
        ->and($checkIn->eventSession->id)->toBe($session->id)
        ->and($checkIn->checked_out_at)->toBeNull();
});

it('tracks session reactions', function () {
    $reaction = SessionReaction::factory()->create(['type' => 'lightbulb']);

    expect($reaction->type)->toBe('lightbulb')
        ->and($reaction->user)->toBeInstanceOf(User::class);
});

it('tracks session questions with votes', function () {
    $question = SessionQuestion::factory()->create(['body' => 'How does zero trust work at scale?']);
    $voter = User::factory()->create();

    SessionQuestionVote::create([
        'session_question_id' => $question->id,
        'user_id' => $voter->id,
    ]);

    expect($question->votes)->toHaveCount(1)
        ->and($question->body)->toBe('How does zero trust work at scale?');
});

it('can mark a question as answered', function () {
    $question = SessionQuestion::factory()->create(['is_answered' => true]);

    expect($question->is_answered)->toBeTrue();
});

it('tracks a checkout timestamp', function () {
    $checkIn = SessionCheckIn::factory()->create(['checked_out_at' => now()]);

    expect($checkIn->checked_out_at)->toBeInstanceOf(CarbonInterface::class);
});

it('prevents duplicate votes for the same question and user', function () {
    $question = SessionQuestion::factory()->create();
    $user = User::factory()->create();

    SessionQuestionVote::create([
        'session_question_id' => $question->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => SessionQuestionVote::create([
        'session_question_id' => $question->id,
        'user_id' => $user->id,
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect($question->votes()->count())->toBe(1);
});
