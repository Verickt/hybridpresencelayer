<?php

use App\Models\Event;
use App\Models\User;
use Database\Seeders\DemoEventSeeder;

it('renders the seeded participant journey without browser smoke issues', function () {
    $this->seed(DemoEventSeeder::class);

    $event = Event::where('slug', DemoEventSeeder::EVENT_SLUG)->firstOrFail();
    $participant = User::where('email', DemoEventSeeder::PARTICIPANT_EMAIL)->firstOrFail();
    $liveSession = $event->sessions()->where('title', 'Zero Trust Architecture in 2026')->firstOrFail();
    $cyberBooth = $event->booths()->where('name', 'CyberDefense AG Booth')->firstOrFail();

    $this->actingAs($participant);

    $pages = visit([
        route('event.feed', $event, absolute: false),
        route('event.sessions', $event, absolute: false),
        route('event.sessions.show', [$event, $liveSession], absolute: false),
        route('event.booths', $event, absolute: false),
        route('event.booths.show', [$event, $cyberBooth], absolute: false),
    ]);

    $pages->assertNoSmoke()->assertNoJavaScriptErrors();

    [$feedPage, $sessionsPage, $sessionDetailPage, $boothsPage, $boothDetailPage] = $pages;

    $feedPage
        ->assertSee('Taylor Brooks')
        ->assertSee('Maya Patel')
        ->assertSee('Presence Feed');

    $sessionsPage
        ->assertSee('Zero Trust Architecture in 2026')
        ->assertSee('Data Privacy Leadership Roundtable');

    $sessionDetailPage
        ->assertSee('Taylor Brooks')
        ->assertSee('How do you phase Zero Trust into a legacy environment without freezing delivery?')
        ->assertSee('What finally got leadership to fund the second rollout instead of walking away?');

    $boothsPage
        ->assertSee('CyberDefense AG Booth')
        ->assertSee('CloudScale Solutions Booth');

    $boothDetailPage
        ->assertSee('Incident response playbook')
        ->assertSee('Ava Keller')
        ->assertSee('Maya Patel');
});

it('renders the seeded organizer dashboard without browser smoke issues', function () {
    $this->seed(DemoEventSeeder::class);

    $event = Event::where('slug', DemoEventSeeder::EVENT_SLUG)->firstOrFail();
    $organizer = User::where('email', DemoEventSeeder::ORGANIZER_EMAIL)->firstOrFail();

    $this->actingAs($organizer);

    visit(route('event.dashboard', $event, absolute: false))
        ->assertSee('Organizer overview')
        ->assertSee('Session analytics')
        ->assertSee('Booth performance')
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});
