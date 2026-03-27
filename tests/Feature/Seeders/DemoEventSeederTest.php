<?php

use App\Models\BoothVisit;
use App\Models\Connection;
use App\Models\Event;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\Suggestion;
use App\Models\User;
use Database\Seeders\DemoEventSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

it('seeds a useful demo dataset for browsing the current app', function () {
    $this->seed(DemoEventSeeder::class);

    $event = Event::where('slug', DemoEventSeeder::EVENT_SLUG)->firstOrFail();
    $organizer = User::where('email', DemoEventSeeder::ORGANIZER_EMAIL)->firstOrFail();
    $participant = User::where('email', DemoEventSeeder::PARTICIPANT_EMAIL)->firstOrFail();
    $liveSession = $event->sessions()->where('title', 'Zero Trust Architecture in 2026')->firstOrFail();
    $cyberBooth = $event->booths()->where('name', 'CyberDefense AG Booth')->firstOrFail();

    expect($organizer->is_organizer)->toBeTrue()
        ->and(Hash::check(DemoEventSeeder::DEMO_PASSWORD, $organizer->password))->toBeTrue()
        ->and(Hash::check(DemoEventSeeder::DEMO_PASSWORD, $participant->password))->toBeTrue()
        ->and($event->isLive())->toBeTrue()
        ->and($event->sessions()->count())->toBe(5)
        ->and($event->booths()->count())->toBe(4)
        ->and($event->participants()->count())->toBe(12)
        ->and(SessionQuestion::whereIn('event_session_id', $event->sessions()->pluck('id'))->count())->toBe(6)
        ->and(SessionQuestionVote::count())->toBe(12)
        ->and(BoothVisit::whereIn('booth_id', $event->booths()->pluck('id'))->count())->toBe(6)
        ->and(Connection::where('event_id', $event->id)->count())->toBe(3)
        ->and(Suggestion::where('event_id', $event->id)->count())->toBe(3)
        ->and($participant->unreadNotifications()->count())->toBe(4)
        ->and($organizer->unreadNotifications()->count())->toBe(2);

    $this->actingAs($participant)
        ->get(route('event.feed', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Feed')
            ->has('participants', 12)
            ->where('event.slug', DemoEventSeeder::EVENT_SLUG)
            ->etc()
        );

    $this->actingAs($participant)
        ->get(route('event.sessions', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Sessions')
            ->has('sessions', 5)
            ->where('sessions.1.title', 'Zero Trust Architecture in 2026')
            ->where('sessions.1.attendee_count', 3)
            ->etc()
        );

    $this->actingAs($participant)
        ->get(route('event.sessions.show', [$event, $liveSession]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/SessionDetail')
            ->where('session.title', 'Zero Trust Architecture in 2026')
            ->has('participants', 3)
            ->has('questions', 2)
            ->where('questions.0.votes_count', 3)
            ->etc()
        );

    $this->actingAs($participant)
        ->get(route('event.booths', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Booths')
            ->has('booths', 4)
            ->where('booths.0.name', 'CyberDefense AG Booth')
            ->where('booths.0.visitor_count', 2)
            ->etc()
        );

    $this->actingAs($participant)
        ->get(route('event.booths.show', [$event, $cyberBooth]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/BoothDetail')
            ->where('booth.name', 'CyberDefense AG Booth')
            ->where('booth.content_links.0.label', 'Incident response playbook')
            ->where('booth.content_links.0.url', 'https://demo.test/cyberdefense/playbook')
            ->has('visitors', 2)
            ->etc()
        );

    $this->actingAs($participant)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->actingAs($participant)
        ->get(route('event.suggestions', $event))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($organizer)
        ->get(route('event.dashboard', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Dashboard')
            ->where('event.slug', DemoEventSeeder::EVENT_SLUG)
            ->where('overview.total_active', 12)
            ->where('overview.total_connections', 3)
            ->etc()
        );
});

it('can be run repeatedly without duplicating the core demo dataset', function () {
    $this->seed(DemoEventSeeder::class);
    $this->seed(DemoEventSeeder::class);

    $event = Event::where('slug', DemoEventSeeder::EVENT_SLUG)->firstOrFail();

    expect(Event::where('slug', DemoEventSeeder::EVENT_SLUG)->count())->toBe(1)
        ->and(User::where('email', DemoEventSeeder::ORGANIZER_EMAIL)->count())->toBe(1)
        ->and(User::where('email', DemoEventSeeder::PARTICIPANT_EMAIL)->count())->toBe(1)
        ->and($event->sessions()->count())->toBe(5)
        ->and($event->booths()->count())->toBe(4)
        ->and($event->participants()->count())->toBe(12)
        ->and(SessionQuestion::whereIn('event_session_id', $event->sessions()->pluck('id'))->count())->toBe(6)
        ->and(SessionQuestionVote::count())->toBe(12)
        ->and(BoothVisit::whereIn('booth_id', $event->booths()->pluck('id'))->count())->toBe(6)
        ->and(Connection::where('event_id', $event->id)->count())->toBe(3)
        ->and(Suggestion::where('event_id', $event->id)->count())->toBe(3)
        ->and(User::where('email', DemoEventSeeder::PARTICIPANT_EMAIL)->firstOrFail()->unreadNotifications()->count())->toBe(4)
        ->and(User::where('email', DemoEventSeeder::ORGANIZER_EMAIL)->firstOrFail()->unreadNotifications()->count())->toBe(2);
});
