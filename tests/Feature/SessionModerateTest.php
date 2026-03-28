<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\User;

it('allows organizer to pin a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_pinned)->toBeTrue();
});

it('allows organizer to unpin a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'is_pinned' => true,
    ]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_pinned)->toBeFalse();
});

it('allows organizer to hide a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.hide', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_hidden)->toBeTrue();
});

it('allows organizer to mark a question as answered', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.answer', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_answered)->toBeTrue();
    expect($question->fresh()->answered_by)->toBe($organizer->id);
});

it('allows speaker to mark a question as answered', function () {
    $speaker = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['speaker_user_id' => $speaker->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($speaker)->postJson(
        route('event.sessions.questions.answer', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_answered)->toBeTrue();
});

it('rejects moderation from non-organizer non-speaker', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertForbidden();
});

it('renders the session moderate page for organizers', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();

    $response = $this->actingAs($organizer)->get(
        route('event.sessions.moderate', [$event, $session])
    );

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Event/SessionModerate'));
});

it('rejects non-organizer from moderate page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();

    $response = $this->actingAs($user)->get(
        route('event.sessions.moderate', [$event, $session])
    );

    $response->assertForbidden();
});

it('session question has moderation fields', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'is_pinned' => true,
        'is_hidden' => false,
        'answered_by' => $organizer->id,
    ]);

    expect($question->is_pinned)->toBeTrue();
    expect($question->is_hidden)->toBeFalse();
    expect($question->answered_by)->toBe($organizer->id);
    expect($question->answeredByUser)->toBeInstanceOf(User::class);
});
