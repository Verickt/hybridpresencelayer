<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->viewer = User::factory()->create();
    $this->event->participants()->attach($this->viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('returns the presence feed page with the current event and visible participants', function () {
    $visible = User::factory()->create(['name' => 'Visible Remote']);
    $this->event->participants()->attach($visible, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.feed', $this->event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Feed')
            ->has('event', fn (Assert $page) => $page
                ->where('id', $this->event->id)
                ->etc()
            )
            ->has('participants', 2)
            ->has('participants.0', fn (Assert $page) => $page
                ->where('id', $visible->id)
                ->missing('email')
                ->missing('password')
                ->etc()
            )
            ->where('filters.type', 'all')
            ->where('filters.status', 'all')
            ->etc()
        );
});

it('omits sensitive participant attributes from the feed payload', function () {
    $visible = User::factory()->create(['name' => 'Visible Remote']);
    $this->event->participants()->attach($visible, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.feed', $this->event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('participants.0', fn (Assert $participant) => $participant
                ->missing('email')
                ->missing('password')
                ->missing('remember_token')
                ->missing('two_factor_secret')
                ->missing('two_factor_recovery_codes')
                ->etc()
            )
        );
});

it('excludes invisible participants from the feed', function () {
    $hidden = User::factory()->create([
        'name' => 'Hidden Remote',
        'is_invisible' => true,
    ]);

    $this->event->participants()->attach($hidden, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.feed', $this->event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('participants', 1)
            ->missing('participants.1')
        );
});

it('filters participants by type and status', function () {
    $physicalBusy = User::factory()->create(['name' => 'Physical Busy']);
    $remoteAvailable = User::factory()->create(['name' => 'Remote Available']);

    $this->event->participants()->attach($physicalBusy, [
        'participant_type' => 'physical',
        'status' => 'busy',
    ]);

    $this->event->participants()->attach($remoteAvailable, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.feed', [
            'event' => $this->event,
            'type' => 'remote',
            'status' => 'available',
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Feed')
            ->has('participants', 1)
            ->where('filters.type', 'remote')
            ->where('filters.status', 'available')
            ->etc()
        );
});

it('returns an empty participant list when the selected filters match nobody', function () {
    $response = $this->actingAs($this->viewer)
        ->get(route('event.feed', [
            'event' => $this->event,
            'type' => 'remote',
            'status' => 'busy',
        ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.type', 'remote')
            ->where('filters.status', 'busy')
            ->has('participants', 0)
        );
});

it('applies the correct authorization rules for the presence feed', function (string $actorType) {
    if ($actorType === 'guest') {
        $response = $this->get(route('event.feed', $this->event));

        $response->assertRedirect(route('login'));

        return;
    }

    $actor = User::factory()->create();

    if ($actorType === 'different-event participant') {
        $otherEvent = Event::factory()->live()->create();
        $otherEvent->participants()->attach($actor, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    $response = $this->actingAs($actor)
        ->get(route('event.feed', $this->event));

    $response->assertForbidden();
})->with([
    'guest',
    'outsider',
    'different-event participant',
]);
