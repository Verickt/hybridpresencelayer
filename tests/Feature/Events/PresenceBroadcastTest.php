<?php

use App\Events\PresenceStateChanged;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

it('broadcasts a presence state change for a participant', function () {
    EventFacade::fake([PresenceStateChanged::class]);

    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    PresenceStateChanged::dispatch($event, $user, 'busy', 'In a session');

    EventFacade::assertDispatched(PresenceStateChanged::class, function (PresenceStateChanged $broadcastEvent) use ($event, $user) {
        return $broadcastEvent->event->is($event)
            && $broadcastEvent->user->is($user)
            && $broadcastEvent->status === 'busy';
    });
});

it('broadcasts on the event presence channel', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $broadcastEvent = new PresenceStateChanged($event, $user, 'available', 'Watching: Zero Trust Keynote');

    expect($broadcastEvent->broadcastOn()->name)->toBe("private-event.{$event->id}.presence");
});

it('includes the current status payload and timestamp', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $broadcastEvent = new PresenceStateChanged($event, $user, 'busy', 'At Booth: CyberDefense AG Booth');
    $payload = $broadcastEvent->broadcastWith();

    expect($payload)
        ->toMatchArray([
            'user_id' => $user->id,
            'name' => $user->name,
            'status' => 'busy',
            'context_badge' => 'At Booth: CyberDefense AG Booth',
            'participant_type' => 'remote',
        ])
        ->and($payload['occurred_at'])->toBeString();
});

it('does not leak sensitive user data in the broadcast payload', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $payload = (new PresenceStateChanged($event, $user, 'away'))->broadcastWith();

    expect($payload)
        ->not->toHaveKey('email')
        ->not->toHaveKey('password')
        ->not->toHaveKey('two_factor_secret');
});
