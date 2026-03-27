<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
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
        'qa_enabled' => true,
    ]);

    SessionCheckIn::create([
        'user_id' => $this->participant->id,
        'event_session_id' => $this->session->id,
    ]);
});

it('submits a question', function () {
    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => 'How does zero trust work at scale?',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Question submitted');

    expect(SessionQuestion::count())->toBe(1);
});

it('rejects empty questions', function () {
    $response = $this->actingAs($this->participant)
        ->postJson(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => '',
        ]);

    $response->assertUnprocessable();
});

it('upvotes a question', function () {
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $this->session->id,
        'user_id' => $this->participant->id,
        'body' => 'What happens next?',
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response->assertOk()
        ->assertJsonPath('message', 'Vote recorded');

    expect(SessionQuestionVote::count())->toBe(1);
});

it('prevents duplicate votes', function () {
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $this->session->id,
        'user_id' => $this->participant->id,
        'body' => 'What happens next?',
    ]);

    $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response->assertStatus(409);
});

it('rejects questions when Q&A is disabled', function () {
    $this->session->update(['qa_enabled' => false]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => 'Can you still ask this?',
        ]);

    $response->assertForbidden();
});

it('rejects questions after the session has ended', function () {
    $this->session->update([
        'ends_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => 'Is this still live?',
        ]);

    $response->assertUnprocessable();
});

it('rejects cross-event question routes', function () {
    $foreignEvent = Event::factory()->live()->create();
    $foreignSession = EventSession::factory()->live()->create([
        'event_id' => $foreignEvent->id,
        'qa_enabled' => true,
    ]);

    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.questions.store', [$this->event, $foreignSession]), [
            'body' => 'Should not work',
        ]);

    $response->assertNotFound();
});
