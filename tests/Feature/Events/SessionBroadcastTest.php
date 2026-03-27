<?php

use App\Events\SessionQuestionPosted;
use App\Events\SessionReactionSent;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionReaction;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

it('broadcasts session reactions on the session channel', function () {
    $event = Event::factory()->live()->create();
    $session = EventSession::factory()->live()->create(['event_id' => $event->id]);
    $user = User::factory()->create();

    $reaction = SessionReaction::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $user->id,
        'type' => 'lightbulb',
    ]);

    $broadcastEvent = new SessionReactionSent($session, $reaction);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-session.{$session->id}")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'type' => 'lightbulb',
            'user_id' => $user->id,
        ]);
});

it('broadcasts posted session questions with the question author and vote count', function () {
    $event = Event::factory()->live()->create();
    $session = EventSession::factory()->live()->create(['event_id' => $event->id]);
    $user = User::factory()->create();

    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $user->id,
        'body' => 'What happens next?',
    ]);

    $broadcastEvent = new SessionQuestionPosted($session, $question);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-session.{$session->id}")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'id' => $question->id,
            'body' => 'What happens next?',
            'user_name' => $user->name,
            'user_id' => $user->id,
            'votes_count' => 0,
        ]);
});
