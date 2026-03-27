<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;

it('renders the participant event pages with the shared design primitives', function () {
    $event = Event::factory()->live()->create([
        'name' => 'Hybrid Summit',
    ]);
    $participant = User::factory()->create([
        'name' => 'Alex Participant',
    ]);
    $visibleParticipant = User::factory()->create([
        'name' => 'Visible Remote',
    ]);

    $event->participants()->attach($participant, [
        'participant_type' => 'physical',
        'status' => 'available',
        'open_to_call' => true,
    ]);
    $event->participants()->attach($visibleParticipant, [
        'participant_type' => 'remote',
        'status' => 'available',
        'open_to_call' => true,
    ]);

    $session = EventSession::factory()->live()->create([
        'event_id' => $event->id,
        'title' => 'Zero Trust Keynote',
        'speaker' => 'Dana Speaker',
        'room' => 'Stage A',
    ]);

    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Signal Booth',
        'company' => 'Signal Labs',
    ]);
    $booth->staff()->attach($participant);
    $booth->visits()->create([
        'user_id' => $visibleParticipant->id,
        'is_anonymous' => false,
        'participant_type' => 'remote',
        'entered_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($participant);

    [$feedPage, $sessionsPage, $sessionPage, $boothsPage, $boothPage] = visit([
        route('event.feed', $event, absolute: false),
        route('event.sessions', $event, absolute: false),
        route('event.sessions.show', [$event, $session], absolute: false),
        route('event.booths', $event, absolute: false),
        route('event.booths.show', [$event, $booth], absolute: false),
    ]);

    $feedPage
        ->assertSee('Presence Feed')
        ->assertSee('Visible Remote')
        ->assertNoSmoke();

    $sessionsPage
        ->assertSee('Sessions')
        ->assertSee('Zero Trust Keynote')
        ->assertNoSmoke();

    $sessionPage
        ->assertSee('Zero Trust Keynote')
        ->assertSee('Participants')
        ->assertSee('Questions')
        ->assertNoSmoke();

    $boothsPage
        ->assertSee('Booths')
        ->assertSee('Signal Booth')
        ->assertNoSmoke();

    $boothPage
        ->assertSee('Signal Booth')
        ->assertSee('Visitors')
        ->assertSee('Staff')
        ->assertNoSmoke();
});

it('renders the organizer dashboard cards without browser smoke issues', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create([
        'name' => 'Organizer Summit',
        'organizer_id' => $organizer->id,
    ]);

    $this->actingAs($organizer);

    visit(route('event.dashboard', $event, absolute: false))
        ->assertSee('Organizer overview')
        ->assertSee('Session analytics')
        ->assertSee('Booth performance')
        ->assertNoSmoke();
});
