<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organizer = User::factory()->create([
        'name' => 'Event Organizer',
    ]);
    $this->event = Event::factory()->live()->create([
        'organizer_id' => $this->organizer->id,
        'name' => 'Organizer Summit',
    ]);
});

it('renders the organizer dashboard as an inertia page with summary props', function () {
    $this->actingAs($this->organizer)
        ->get(route('event.dashboard', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Dashboard')
            ->where('event.name', 'Organizer Summit')
            ->where('event.slug', $this->event->slug)
            ->has('overview')
            ->has('sessionAnalytics')
            ->has('boothPerformance')
            ->missing('event.organizer_id')
        );
});

it('forbids non-organizers from viewing the dashboard', function () {
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->get(route('event.dashboard', $this->event))
        ->assertForbidden();
});

it('exposes empty-state metrics when there is no activity', function () {
    $response = $this->actingAs($this->organizer)
        ->get(route('event.dashboard', $this->event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('overview.total_active', 0)
            ->where('overview.total_connections', 0)
            ->where('overview.cross_pollination_rate', 0)
            ->has('sessionAnalytics', 0)
            ->has('boothPerformance', 0)
        );
});
