<?php

use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    $this->service = app(NotificationService::class);
});

test('stores the notification payload on the database record', function () {
    $sent = $this->service->send(
        $this->user,
        $this->event,
        'ping',
        'high',
        'Alex wants to connect',
        ['ping_id' => 1]
    );

    expect($sent)->toBeTrue();
    expect($this->user->notifications()->count())->toBe(1);

    $notification = $this->user->notifications()->first();

    expect($notification->data)->toMatchArray([
        'type' => 'ping',
        'priority' => 'high',
        'message' => 'Alex wants to connect',
        'event_id' => $this->event->id,
        'ping_id' => 1,
    ]);
});

test('refuses to notify users who are not attached to the event', function () {
    $otherUser = User::factory()->create();

    $sent = $this->service->send(
        $otherUser,
        $this->event,
        'ping',
        'high',
        'Alex wants to connect'
    );

    expect($sent)->toBeFalse();
    expect($otherUser->notifications()->count())->toBe(0);
});

test('blocks low priority notifications when the participant is busy', function () {
    $this->user->events()->updateExistingPivot($this->event->id, [
        'status' => 'busy',
    ]);

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'low',
            'Check out these matches'
        )
    )->toBeFalse();

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'ping',
            'high',
            'Alex wants to connect'
        )
    )->toBeTrue();

    expect($this->user->notifications()->count())->toBe(1);
});

test('blocks non-high priority notifications in quiet mode but still allows high priority notifications', function () {
    $this->user->events()->updateExistingPivot($this->event->id, [
        'notification_mode' => 'quiet',
    ]);

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggested match'
        )
    )->toBeFalse();

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'ping',
            'high',
            'Alex wants to connect'
        )
    )->toBeTrue();

    expect($this->user->notifications()->count())->toBe(1);
});

test('blocks all notifications in dnd mode', function () {
    $this->user->events()->updateExistingPivot($this->event->id, [
        'notification_mode' => 'dnd',
    ]);

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'ping',
            'high',
            'Alex wants to connect'
        )
    )->toBeFalse();

    expect($this->user->notifications()->count())->toBe(0);
});

test('deduplicates identical notification payloads', function () {
    $firstSend = $this->service->send(
        $this->user,
        $this->event,
        'match',
        'high',
        'You matched with Mira',
        ['connection_id' => 42]
    );

    $secondSend = $this->service->send(
        $this->user,
        $this->event,
        'match',
        'high',
        'You matched with Mira',
        ['connection_id' => 42]
    );

    expect($firstSend)->toBeTrue()
        ->and($secondSend)->toBeFalse()
        ->and($this->user->notifications()->count())->toBe(1);
});

test('ignores stale notifications when enforcing the hourly cap', function () {
    for ($index = 1; $index <= 4; $index++) {
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            "Suggestion {$index}",
            ['suggestion_id' => $index]
        );
    }

    $this->user->notifications()->update([
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggestion 5',
            ['suggestion_id' => 5]
        )
    )->toBeTrue();

    expect($this->user->notifications()->count())->toBe(5);
});

test('blocks medium priority notifications after the hourly cap is reached', function () {
    for ($index = 1; $index <= 4; $index++) {
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            "Suggestion {$index}",
            ['suggestion_id' => $index]
        );
    }

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggestion 5',
            ['suggestion_id' => 5]
        )
    )->toBeFalse();

    expect($this->user->notifications()->count())->toBe(4);
});

test('allows medium priority notifications again after the hourly window passes', function () {
    for ($index = 1; $index <= 4; $index++) {
        expect(
            $this->service->send(
                $this->user,
                $this->event,
                'suggestion',
                'medium',
                "Suggestion {$index}",
                ['suggestion_id' => $index]
            )
        )->toBeTrue();
    }

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggestion 5',
            ['suggestion_id' => 5]
        )
    )->toBeFalse();

    $this->travel(61)->minutes();

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggestion 6',
            ['suggestion_id' => 6]
        )
    )->toBeTrue();
});

test('enforces the daily cap for medium priority notifications even when the hourly window is respected', function () {
    foreach (range(1, 5) as $block) {
        foreach (range(1, 4) as $offset) {
            $index = (($block - 1) * 4) + $offset;

            expect(
                $this->service->send(
                    $this->user,
                    $this->event,
                    'suggestion',
                    'medium',
                    "Suggestion {$index}",
                    ['suggestion_id' => $index]
                )
            )->toBeTrue();
        }

        if ($block < 5) {
            $this->travel(61)->minutes();
        }
    }

    expect(
        $this->service->send(
            $this->user,
            $this->event,
            'suggestion',
            'medium',
            'Suggestion 21',
            ['suggestion_id' => 21]
        )
    )->toBeFalse();

    expect($this->user->notifications()->count())->toBe(20);
});

test('does not cap high priority notifications', function () {
    foreach (range(1, 25) as $index) {
        expect(
            $this->service->send(
                $this->user,
                $this->event,
                'ping',
                'high',
                "High priority {$index}",
                ['ping_id' => $index]
            )
        )->toBeTrue();
    }

    expect($this->user->notifications()->count())->toBe(25);
});
