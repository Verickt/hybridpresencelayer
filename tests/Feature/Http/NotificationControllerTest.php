<?php

use App\Models\Event;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);
});

test('guests are redirected to login', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

test('lists unread notifications and omits read notifications', function () {
    $this->user->notify(new InAppNotification(
        'ping',
        'high',
        'Alex pinged you',
        $this->event->id,
        ['ping_id' => 1],
    ));

    $readNotification = $this->user->notifications()->first();
    $readNotification->markAsRead();

    $this->user->notify(new InAppNotification(
        'match',
        'high',
        'You matched with Mira',
        $this->event->id,
        ['connection_id' => 42],
    ));

    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.0', fn (AssertableJson $notification) => $notification
                ->where('type', 'match')
                ->where('priority', 'high')
                ->where('message', 'You matched with Mira')
                ->where('data.event_id', $this->event->id)
                ->where('data.connection_id', 42)
                ->missing('notifiable_id')
                ->missing('notifiable_type')
                ->missing('read_at')
                ->etc()
            )
        );
});

test('marks a notification as read', function () {
    $this->user->notify(new InAppNotification(
        'ping',
        'high',
        'Alex pinged you',
        $this->event->id,
        ['ping_id' => 1],
    ));

    $notification = $this->user->notifications()->first();

    $this->actingAs($this->user)
        ->patch(route('notifications.read', $notification->id))
        ->assertOk()
        ->assertJson([
            'message' => 'Als gelesen markiert',
        ]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('decrements the unread count after a notification is marked as read', function () {
    $this->user->notify(new InAppNotification(
        'ping',
        'high',
        'Alex pinged you',
        $this->event->id,
        ['ping_id' => 1],
    ));

    $notification = $this->user->notifications()->first();

    $this->actingAs($this->user)
        ->patch(route('notifications.read', $notification->id))
        ->assertOk();

    $this->actingAs($this->user)
        ->get(route('notifications.count'))
        ->assertOk()
        ->assertJson([
            'count' => 0,
        ]);
});

test('rejects reading another users notification', function () {
    $otherUser = User::factory()->create();

    $otherUser->notify(new InAppNotification(
        'ping',
        'high',
        'Alex pinged you',
        $this->event->id,
        ['ping_id' => 1],
    ));

    $otherNotification = $otherUser->notifications()->first();

    $this->actingAs($this->user)
        ->patch(route('notifications.read', $otherNotification->id))
        ->assertNotFound();

    expect($otherNotification->fresh()->read_at)->toBeNull();
});

test('returns the unread count for the authenticated user', function () {
    $this->user->notify(new InAppNotification(
        'ping',
        'high',
        'Alex pinged you',
        $this->event->id,
        ['ping_id' => 1],
    ));

    $this->user->notify(new InAppNotification(
        'match',
        'high',
        'You matched with Mira',
        $this->event->id,
        ['connection_id' => 42],
    ));

    $this->actingAs($this->user)
        ->get(route('notifications.count'))
        ->assertOk()
        ->assertJson([
            'count' => 2,
        ]);
});

test('returns zero unread notifications for an empty inbox', function () {
    $this->actingAs($this->user)
        ->get(route('notifications.count'))
        ->assertOk()
        ->assertJson([
            'count' => 0,
        ]);
});

test('updates notification preferences for an attached participant', function () {
    $this->actingAs($this->user)
        ->patch(route('event.notification-prefs', $this->event), [
            'notification_mode' => 'quiet',
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Einstellungen aktualisiert',
        ]);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->notification_mode)->toBe('quiet');
});

test('rejects invalid notification preference values', function ($value) {
    $this->actingAs($this->user)
        ->from(route('event.notification-prefs', $this->event))
        ->patch(route('event.notification-prefs', $this->event), [
            'notification_mode' => $value,
        ])
        ->assertSessionHasErrors('notification_mode');
})->with([
    'loud',
    '',
    null,
]);

test('rejects preference updates when the user is not a participant', function () {
    $otherEvent = Event::factory()->live()->create();

    $this->actingAs($this->user)
        ->patch(route('event.notification-prefs', $otherEvent), [
            'notification_mode' => 'quiet',
        ])
        ->assertNotFound();
});

test('requires authentication to update notification preferences', function () {
    $this->patch(route('event.notification-prefs', $this->event), [
        'notification_mode' => 'quiet',
    ])->assertRedirect(route('login'));
});
