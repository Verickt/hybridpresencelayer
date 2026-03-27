<?php

use App\Models\Event;
use App\Models\User;

it('renders the event shell navigation on the profile page', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    $this->actingAs($user);

    visit(route('event.profile', $event, absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee('Your Profile')
        ->assertPresent('@event-tab-feed')
        ->assertPresent('@event-tab-sessions')
        ->assertPresent('@event-tab-connections')
        ->assertPresent('@event-tab-profile')
        ->assertPresent('@notification-bell')
        ->assertPresent('@qr-scan-button')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('shows the manual fallback when camera permission is denied', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    $this->actingAs($user);

    $page = visit(route('event.profile', $event, absolute: false));

    $page->script(<<<'JS'
        Object.defineProperty(navigator, 'mediaDevices', {
            configurable: true,
            value: {
                getUserMedia: () => Promise.reject(new Error('Camera denied')),
            },
        });
    JS);

    $page->click('@qr-scan-button')
        ->assertPresent('@qr-manual-fallback')
        ->assertSeeIn('@qr-manual-fallback', 'Camera not available. Use manual check-in instead.')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('shows the manual fallback when the browser has no camera support', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    $this->actingAs($user);

    $page = visit(route('event.profile', $event, absolute: false));

    $page->script(<<<'JS'
        Object.defineProperty(navigator, 'mediaDevices', {
            configurable: true,
            value: undefined,
        });
    JS);

    $page->click('@qr-scan-button')
        ->assertPresent('@qr-manual-fallback')
        ->assertSeeIn('@qr-manual-fallback', 'Camera not available. Use manual check-in instead.')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});
