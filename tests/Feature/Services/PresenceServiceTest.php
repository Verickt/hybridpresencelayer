<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Services\PresenceService;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->service = app(PresenceService::class);
});

it('updates participant status and records last activity', function () {
    $this->service->updateStatus($this->user, $this->event, 'busy');

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('busy')
        ->and($pivot->last_active_at)->not->toBeNull();
});

it('rejects invalid status values', function () {
    $this->service->updateStatus($this->user, $this->event, 'dancing');
})->throws(InvalidArgumentException::class);

it('rejects status updates for users who are not participants', function () {
    $outsider = User::factory()->create();

    $this->service->updateStatus($outsider, $this->event, 'busy');
})->throws(AuthorizationException::class);

it('sets context badge when checking into a session', function () {
    $session = EventSession::factory()->create([
        'event_id' => $this->event->id,
        'title' => 'Zero Trust Keynote',
    ]);

    $this->service->checkInToSession($this->user, $this->event, $session);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toBe('In session: Zero Trust Keynote');
});

it('clears context badge when checking out of a session', function () {
    $session = EventSession::factory()->create(['event_id' => $this->event->id]);

    $this->service->checkInToSession($this->user, $this->event, $session);
    $this->service->checkOutOfSession($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('available')
        ->and($pivot->context_badge)->toBeNull();
});

it('sets context badge when visiting a booth', function () {
    $booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'CyberDefense AG Booth',
    ]);

    $this->service->checkInToBooth($this->user, $this->event, $booth);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('at_booth')
        ->and($pivot->context_badge)->toBe('At Booth: CyberDefense AG Booth');
});

it('replaces booth context with session context when the participant moves into a session', function () {
    $booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'CyberDefense AG Booth',
    ]);
    $session = EventSession::factory()->create([
        'event_id' => $this->event->id,
        'title' => 'Zero Trust Keynote',
    ]);

    $this->service->checkInToBooth($this->user, $this->event, $booth);
    $this->service->checkInToSession($this->user, $this->event, $session);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toBe('In session: Zero Trust Keynote');
});

it('rejects session check-ins for users who are not participants', function () {
    $outsider = User::factory()->create();
    $session = EventSession::factory()->create([
        'event_id' => $this->event->id,
    ]);

    $this->service->checkInToSession($outsider, $this->event, $session);
})->throws(AuthorizationException::class);

it('marks a participant as away after inactivity', function () {
    $this->service->markInactive($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('away');
});

it('updates last_active_at on any touch action', function () {
    $this->service->touch($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->last_active_at)->not->toBeNull();
});

it('toggles invisible mode on the user profile', function () {
    $this->service->toggleInvisible($this->user);

    expect($this->user->fresh()->is_invisible)->toBeTrue();

    $this->service->toggleInvisible($this->user);

    expect($this->user->fresh()->is_invisible)->toBeFalse();
});
