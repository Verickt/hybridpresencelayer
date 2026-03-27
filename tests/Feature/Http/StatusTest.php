<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->viewer = User::factory()->create();
    $this->event->participants()->attach($this->viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('updates the participant status via the API', function () {
    $response = $this->actingAs($this->viewer)
        ->patch(route('event.status.update', $this->event), [
            'status' => 'busy',
        ]);

    $response->assertOk();

    $pivot = $this->viewer->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('busy');
});

it('rejects invalid status values', function (string $status) {
    $this->actingAs($this->viewer)
        ->patch(route('event.status.update', $this->event), [
            'status' => $status,
        ])
        ->assertUnprocessable();
})->with([
    'dancing',
    '',
    'available_later',
]);

it('toggles invisible mode', function () {
    $response = $this->actingAs($this->viewer)
        ->patch(route('event.status.invisible', $this->event));

    $response->assertOk();

    expect($this->viewer->fresh()->is_invisible)->toBeTrue();
});

it('updates status only for the targeted event membership', function () {
    $otherEvent = Event::factory()->live()->create();

    $otherEvent->participants()->attach($this->viewer, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->actingAs($this->viewer)
        ->patch(route('event.status.update', $this->event), [
            'status' => 'busy',
        ])
        ->assertOk();

    $currentPivot = $this->viewer->events()->where('event_id', $this->event->id)->first()->pivot;
    $otherPivot = $this->viewer->events()->where('event_id', $otherEvent->id)->first()->pivot;

    expect($currentPivot->status)->toBe('busy')
        ->and($otherPivot->status)->toBe('available');
});

it('applies the correct authorization rules for status updates', function (string $actorType) {
    if ($actorType === 'guest') {
        $this->patch(route('event.status.update', $this->event), [
            'status' => 'busy',
        ])->assertRedirect(route('login'));

        return;
    }

    $actor = User::factory()->create();

    if ($actorType === 'different-event participant') {
        $otherEvent = Event::factory()->live()->create();
        $otherEvent->participants()->attach($actor, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    $this->actingAs($actor)
        ->patch(route('event.status.update', $this->event), [
            'status' => 'busy',
        ])
        ->assertForbidden();
})->with([
    'guest',
    'outsider',
    'different-event participant',
]);
