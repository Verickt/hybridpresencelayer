<?php

use App\Models\Event;
use App\Models\EventSession;
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
