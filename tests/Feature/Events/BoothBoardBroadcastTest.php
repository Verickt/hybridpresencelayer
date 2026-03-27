<?php

use App\Events\BoothDemoEnded;
use App\Events\BoothDemoStarted;
use App\Events\BoothThreadPosted;
use App\Events\BoothThreadReplyPosted;
use App\Events\BoothThreadVoted;
use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\BoothThreadReply;
use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

it('broadcasts new booth threads on the booth channel', function () {
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();

    $thread = BoothThread::factory()->create([
        'booth_id' => $booth->id,
        'user_id' => $user->id,
        'body' => 'Can we get the deck afterwards?',
    ]);

    $broadcastEvent = new BoothThreadPosted($booth, $thread);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-booth.{$booth->id}")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'id' => $thread->id,
            'body' => 'Can we get the deck afterwards?',
            'kind' => 'question',
            'votes_count' => 0,
        ]);
});

it('broadcasts booth replies on the booth channel', function () {
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);
    $staff = User::factory()->create();
    $thread = BoothThread::factory()->create([
        'booth_id' => $booth->id,
        'user_id' => $staff->id,
    ]);
    $reply = BoothThreadReply::factory()->create([
        'booth_thread_id' => $thread->id,
        'user_id' => $staff->id,
        'body' => 'Yes, we will publish them later today.',
        'is_staff_answer' => true,
    ]);

    $broadcastEvent = new BoothThreadReplyPosted($booth, $thread, $reply);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-booth.{$booth->id}")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'thread_id' => $thread->id,
            'reply_id' => $reply->id,
            'body' => 'Yes, we will publish them later today.',
            'is_staff_answer' => true,
        ]);
});

it('broadcasts booth vote totals on the booth channel', function () {
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);
    $thread = BoothThread::factory()->create(['booth_id' => $booth->id]);

    $broadcastEvent = new BoothThreadVoted($booth, $thread, 3);

    expect($broadcastEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($broadcastEvent->broadcastOn()->name)->toBe("private-booth.{$booth->id}")
        ->and($broadcastEvent->broadcastWith())->toMatchArray([
            'thread_id' => $thread->id,
            'votes_count' => 3,
        ]);
});

it('broadcasts booth demo lifecycle events on the booth channel', function () {
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);
    $staff = User::factory()->create();
    $demo = BoothDemo::factory()->create([
        'booth_id' => $booth->id,
        'started_by_user_id' => $staff->id,
        'title' => 'Live walkthrough',
        'status' => 'live',
    ]);

    $startedEvent = new BoothDemoStarted($booth, $demo);
    $endedDemo = $demo->fresh()->fill([
        'status' => 'ended',
        'ended_at' => now(),
    ]);
    $endedEvent = new BoothDemoEnded($booth, $endedDemo);

    expect($startedEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($startedEvent->broadcastOn()->name)->toBe("private-booth.{$booth->id}")
        ->and($startedEvent->broadcastWith())->toMatchArray([
            'demo_id' => $demo->id,
            'title' => 'Live walkthrough',
            'status' => 'live',
        ])
        ->and($endedEvent)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($endedEvent->broadcastOn()->name)->toBe("private-booth.{$booth->id}")
        ->and($endedEvent->broadcastWith())->toMatchArray([
            'demo_id' => $demo->id,
            'status' => 'ended',
        ]);
});
