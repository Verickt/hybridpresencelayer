<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionQuestionReplyVote;
use App\Models\User;
use Illuminate\Database\QueryException;

it('creates a reply on a session question', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $author = User::factory()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $author->id,
        'body' => 'Great question, here is my take on it.',
    ]);

    expect($reply)->toBeInstanceOf(SessionQuestionReply::class);
    expect($reply->question->id)->toBe($question->id);
    expect($reply->user->id)->toBe($author->id);
    expect($question->replies)->toHaveCount(1);
});

it('prevents duplicate votes on a reply', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);
    $voter = User::factory()->create();

    SessionQuestionReplyVote::create([
        'session_question_reply_id' => $reply->id,
        'user_id' => $voter->id,
    ]);

    expect(fn () => SessionQuestionReplyVote::create([
        'session_question_reply_id' => $reply->id,
        'user_id' => $voter->id,
    ]))->toThrow(QueryException::class);
});

it('allows a checked-in participant to reply to a question', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.replies.store', [$event, $session, $question]),
        ['body' => 'Here is my answer to your question.']
    );

    $response->assertOk();
    expect($question->replies()->count())->toBe(1);
});

it('rejects reply from non-checked-in user', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.replies.store', [$event, $session, $question]),
        ['body' => 'Should not work']
    );

    $response->assertForbidden();
});

it('allows a checked-in participant to vote on a reply', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $voter = User::factory()->create();
    $event->participants()->attach($voter, ['participant_type' => 'remote', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $voter->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);

    $response = $this->actingAs($voter)->postJson(
        route('event.sessions.questions.replies.vote', [$event, $session, $question, $reply])
    );

    $response->assertOk();
    expect($reply->votes()->count())->toBe(1);
});

it('returns 409 for duplicate reply vote', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $voter = User::factory()->create();
    $event->participants()->attach($voter, ['participant_type' => 'remote', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $voter->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);
    SessionQuestionReplyVote::create(['session_question_reply_id' => $reply->id, 'user_id' => $voter->id]);

    $response = $this->actingAs($voter)->postJson(
        route('event.sessions.questions.replies.vote', [$event, $session, $question, $reply])
    );

    $response->assertConflict();
});
