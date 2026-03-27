<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create();

    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);
});

it('checks into a session and updates the presence context', function () {
    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.checkin', [$this->event, $this->session]));

    $response->assertOk();

    expect(SessionCheckIn::count())->toBe(1);

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toBe("In session: {$this->session->title}");
});

it('checks out of a session and restores availability', function () {
    SessionCheckIn::create([
        'user_id' => $this->participant->id,
        'event_session_id' => $this->session->id,
    ]);

    $response = $this->actingAs($this->participant)
        ->delete(route('event.sessions.checkout', [$this->event, $this->session]));

    $response->assertOk();

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('available')
        ->and($pivot->context_badge)->toBeNull();
});

it('auto-checks out of the previous session when checking into a new one', function () {
    $secondSession = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);

    $this->actingAs($this->participant)
        ->post(route('event.sessions.checkin', [$this->event, $this->session]));

    $this->actingAs($this->participant)
        ->post(route('event.sessions.checkin', [$this->event, $secondSession]));

    expect(SessionCheckIn::whereNull('checked_out_at')->count())->toBe(1)
        ->and(SessionCheckIn::whereNotNull('checked_out_at')->count())->toBe(1);
});

it('keeps checkout idempotent when no active check-in exists', function () {
    $response = $this->actingAs($this->participant)
        ->delete(route('event.sessions.checkout', [$this->event, $this->session]));

    $response->assertOk();

    expect(SessionCheckIn::count())->toBe(0);
});

it('rejects cross-event check-ins', function () {
    $foreignEvent = Event::factory()->live()->create();
    $foreignSession = EventSession::factory()->live()->create([
        'event_id' => $foreignEvent->id,
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.checkin', [$this->event, $foreignSession]));

    $response->assertNotFound();
});

it('rejects check-ins outside the session join window', function () {
    $futureSession = EventSession::factory()->create([
        'event_id' => $this->event->id,
        'starts_at' => now()->addMinutes(20),
        'ends_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.checkin', [$this->event, $futureSession]));

    $response->assertUnprocessable();

    expect(SessionCheckIn::count())->toBe(0);
});
