<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionReaction;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);

    SessionCheckIn::create([
        'user_id' => $this->participant->id,
        'event_session_id' => $this->session->id,
    ]);
});

it('sends a reaction to a live session', function () {
    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'lightbulb',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Reaction sent');

    expect(SessionReaction::count())->toBe(1);
});

it('rejects invalid reaction types', function () {
    $response = $this->actingAs($this->participant)
        ->postJson(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'thumbsup',
        ]);

    $response->assertUnprocessable();
});

it('rejects reactions from users outside the session', function () {
    $response = $this->actingAs($this->outsider)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'clap',
        ]);

    $response->assertForbidden();
});

it('rejects reactions after the session has ended', function () {
    $this->session->update([
        'ends_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'fire',
        ]);

    $response->assertUnprocessable();
});

it('rejects reactions when the session has reactions disabled', function () {
    $this->session->update([
        'reactions_enabled' => false,
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'fire',
        ]);

    $response->assertForbidden();
});
