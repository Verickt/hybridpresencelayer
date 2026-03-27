<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\User;

it('lets a joined participant react, ask a question, and vote from the session page', function () {
    $event = Event::factory()->live()->create();
    $participant = User::factory()->create();
    $questionAuthor = User::factory()->create();

    $event->participants()->attach($participant, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $event->participants()->attach($questionAuthor, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
        'title' => 'Zero Trust Architecture in 2026',
        'qa_enabled' => true,
        'reactions_enabled' => true,
    ]);

    $seededQuestion = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $questionAuthor->id,
        'body' => 'What should the team automate next?',
    ]);

    $this->actingAs($participant);

    visit(route('event.sessions.show', [$event, $session], absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee('Join session')
        ->click('Join session')
        ->assertSee('In Session')
        ->click('Clap')
        ->assertSee('Reaction sent')
        ->fill('textarea[name="body"]', 'How do remote attendees join?')
        ->click('Submit question')
        ->assertSee('How do remote attendees join?')
        ->click('Vote')
        ->assertSee('Vote recorded')
        ->assertSee('Voted')
        ->assertNoSmoke();
});
