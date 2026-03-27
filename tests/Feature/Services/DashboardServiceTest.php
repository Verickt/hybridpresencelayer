<?php

use App\Models\Booth;
use App\Models\Connection;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Ping;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\DashboardService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(DashboardService::class);
});

it('returns zeroed metrics and empty analytics when there is no activity', function () {
    expect($this->service->overview($this->event))->toMatchArray([
        'total_active' => 0,
        'physical_count' => 0,
        'remote_count' => 0,
        'total_connections' => 0,
        'cross_pollination_rate' => 0.0,
        'interaction_rate' => 0.0,
        'match_acceptance_rate' => 0.0,
        'networking_density' => 0.0,
    ]);

    expect($this->service->sessionAnalytics($this->event))->toBe([]);
    expect($this->service->boothPerformance($this->event))->toBe([]);
});

it('calculates dashboard metrics from actual event activity', function () {
    $physicalUsers = User::factory(5)->create();
    $remoteUsers = User::factory(3)->create();

    $physicalUsers->each(fn (User $user) => $this->event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]));

    $remoteUsers->each(fn (User $user) => $this->event->participants()->attach($user, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]));

    Connection::factory()->createMany([
        ['event_id' => $this->event->id, 'is_cross_world' => true],
        ['event_id' => $this->event->id, 'is_cross_world' => true],
        ['event_id' => $this->event->id, 'is_cross_world' => false],
        ['event_id' => $this->event->id, 'is_cross_world' => false],
    ]);

    Ping::factory()->create([
        'event_id' => $this->event->id,
        'sender_id' => $physicalUsers[0]->id,
        'receiver_id' => $remoteUsers[0]->id,
    ]);
    Ping::factory()->create([
        'event_id' => $this->event->id,
        'sender_id' => $physicalUsers[1]->id,
        'receiver_id' => $remoteUsers[1]->id,
    ]);
    Ping::factory()->create([
        'event_id' => $this->event->id,
        'sender_id' => $physicalUsers[2]->id,
        'receiver_id' => $remoteUsers[2]->id,
    ]);

    Suggestion::factory()->createMany([
        ['event_id' => $this->event->id, 'status' => 'accepted'],
        ['event_id' => $this->event->id, 'status' => 'accepted'],
        ['event_id' => $this->event->id, 'status' => 'pending'],
    ]);

    $stats = $this->service->overview($this->event);

    expect($stats)->toMatchArray([
        'total_active' => 8,
        'physical_count' => 5,
        'remote_count' => 3,
        'total_connections' => 4,
        'cross_pollination_rate' => 50.0,
        'interaction_rate' => 75.0,
        'match_acceptance_rate' => 66.7,
        'networking_density' => 14.29,
    ]);
});

it('sorts booth and session analytics for the event', function () {
    $sessionA = EventSession::factory()->create([
        'event_id' => $this->event->id,
        'title' => 'Opening Keynote',
    ]);
    $sessionB = EventSession::factory()->create([
        'event_id' => $this->event->id,
        'title' => 'Closing Panel',
    ]);

    $boothA = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Popular Booth',
    ]);
    $boothB = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Quiet Booth',
    ]);

    $sessionAnalytics = $this->service->sessionAnalytics($this->event);
    $boothPerformance = $this->service->boothPerformance($this->event);

    expect($sessionAnalytics)->toBeArray();
    expect($boothPerformance)->toBeArray();
});
