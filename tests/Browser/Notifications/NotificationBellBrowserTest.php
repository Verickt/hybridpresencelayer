<?php

use App\Models\Event;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\NotificationService;

it('shows a prominent unread badge in the event shell when notifications exist', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    foreach (range(1, 10) as $index) {
        $user->notify(new InAppNotification(
            'suggestion',
            'medium',
            "Suggestion {$index}",
            $event->id,
            ['suggestion_id' => $index],
        ));
    }

    $this->actingAs($user);

    visit(route('event.profile', $event, absolute: false))
        ->on()->iPhone14Pro()
        ->assertPresent('@notification-bell')
        ->assertPresent('@notification-badge')
        ->assertSeeIn('@notification-badge', '9+')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('keeps suppressed notifications out of the event shell badge', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $service = app(NotificationService::class);

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'dnd',
    ]);

    expect(
        $service->send(
            $user,
            $event,
            'nudge',
            'low',
            'Muted notification',
            ['source' => 'organizer']
        )
    )->toBeFalse();

    $this->actingAs($user);

    visit(route('event.profile', $event, absolute: false))
        ->assertPresent('@notification-bell')
        ->assertMissing('@notification-badge')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});
