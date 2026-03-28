<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionQuestionVote;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create();
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->organizer = $this->event->organizer;
});

it('shows the session schedule with an empty state', function () {
    $response = $this->actingAs($this->participant)
        ->get(route('event.sessions', $this->event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Sessions')
            ->where('event.slug', $this->event->slug)
            ->has('sessions', 0)
            ->etc()
        );
});

it('shows session detail with viewer state, live participants, and questions', function () {
    $session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);

    SessionCheckIn::create([
        'user_id' => $this->participant->id,
        'event_session_id' => $session->id,
    ]);

    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $this->participant->id,
        'body' => 'How does zero trust work at scale?',
    ]);

    SessionQuestionVote::create([
        'session_question_id' => $question->id,
        'user_id' => $this->participant->id,
    ]);

    $response = $this->actingAs($this->participant)
        ->get(route('event.sessions.show', [$this->event, $session]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/SessionDetail')
            ->where('event.slug', $this->event->slug)
            ->where('session.title', $session->title)
            ->where('session.is_joinable', true)
            ->where('session.can_interact', true)
            ->where('viewer.is_organizer', false)
            ->where('viewer.is_checked_in', true)
            ->where('viewer.can_join', true)
            ->where('viewer.can_interact', true)
            ->has('participants', 1)
            ->has('questions', 1)
            ->where('questions.0.viewer_has_voted', true)
            ->etc()
        );
});

it('blocks guests from the session schedule', function () {
    $response = $this->get(route('event.sessions', $this->event));

    $response->assertRedirect(route('login'));
});

it('blocks cross-event access to a session', function () {
    $foreignEvent = Event::factory()->live()->create();
    $foreignSession = EventSession::factory()->live()->create([
        'event_id' => $foreignEvent->id,
    ]);

    $response = $this->actingAs($this->participant)
        ->get(route('event.sessions.show', [$this->event, $foreignSession]));

    $response->assertNotFound();
});

it('allows the organizer to view the session detail without being a participant', function () {
    $session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->organizer)
        ->get(route('event.sessions.show', [$this->event, $session]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/SessionDetail')
            ->where('viewer.is_organizer', true)
            ->where('viewer.is_checked_in', false)
            ->where('viewer.can_join', false)
            ->where('viewer.can_interact', false)
            ->etc()
        );
});

it('returns questions with replies and moderation data in session show', function () {
    $organizer = $this->organizer;
    $event = $this->event;
    $session = EventSession::factory()->for($event)->live()->create(['speaker_user_id' => $organizer->id]);
    $user = $this->participant;
    $event->participants()->syncWithoutDetaching([$user->id => ['participant_type' => 'physical', 'status' => 'in_session']]);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);

    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id, 'is_pinned' => true]);
    SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $organizer->id,
        'body' => 'Great question!',
    ]);

    $response = $this->actingAs($user)->get(route('event.sessions.show', [$event, $session]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Event/SessionDetail')
        ->has('questions.0.replies', 1)
        ->where('questions.0.is_pinned', true)
        ->where('questions.0.is_hidden', false)
    );
});

it('forbids outsiders from the session pages', function () {
    $outsider = User::factory()->create();
    $session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
    ]);

    $this->actingAs($outsider)
        ->get(route('event.sessions', $this->event))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get(route('event.sessions.show', [$this->event, $session]))
        ->assertForbidden();
});

it('allows the organizer to create a session', function () {
    $response = $this->actingAs($this->organizer)
        ->post(route('event.sessions.store', $this->event), [
            'title' => 'Zero Trust Keynote',
            'description' => 'A good talk',
            'speaker' => 'Jane Doe',
            'room' => 'Room A',
            'starts_at' => now()->addHour()->toISOString(),
            'ends_at' => now()->addHours(2)->toISOString(),
        ]);

    $response->assertRedirect(route('event.sessions', $this->event));
});

it('rejects non-organizers from creating sessions', function () {
    $response = $this->actingAs($this->participant)
        ->post(route('event.sessions.store', $this->event), [
            'title' => 'Unauthorized Talk',
            'starts_at' => now()->addHour()->toISOString(),
            'ends_at' => now()->addHours(2)->toISOString(),
        ]);

    $response->assertForbidden();
});

it('rejects invalid session time windows', function () {
    $response = $this->actingAs($this->organizer)
        ->postJson(route('event.sessions.store', $this->event), [
            'title' => 'Backwards Session',
            'starts_at' => now()->addHours(2)->toISOString(),
            'ends_at' => now()->addHour()->toISOString(),
        ]);

    $response->assertUnprocessable();
});
