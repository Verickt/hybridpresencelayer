<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->viewer = User::factory()->create();

    $this->event->participants()->attach($this->viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('searches participants by name', function () {
    $target = User::factory()->create(['name' => 'Lena Fischer']);
    $this->event->participants()->attach($target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Lena']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 1)
        );
});

it('searches participants by company', function () {
    $target = User::factory()->create(['company' => 'CyberDefense AG']);
    $this->event->participants()->attach($target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'CyberDefense']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 1)
        );
});

it('searches participants by interest tags', function () {
    $tag = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $target = User::factory()->create();

    $target->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Zero Trust']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 1)
        );
});

it('excludes invisible participants from search', function () {
    $invisible = User::factory()->create([
        'name' => 'Hidden Person',
        'is_invisible' => true,
    ]);

    $this->event->participants()->attach($invisible, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Hidden']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 0)
        );
});

it('omits sensitive fields from search results', function () {
    $target = User::factory()->create(['name' => 'Visible Person']);
    $this->event->participants()->attach($target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Visible']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results.0', fn ($participant) => $participant
                ->where('id', $target->id)
                ->where('name', 'Visible Person')
                ->missing('email')
                ->missing('password')
                ->missing('remember_token')
                ->missing('is_invisible')
                ->etc()
            )
        );
});

it('returns an empty result set when no participants match the query', function () {
    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'No Match Here']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 0)
        );
});

it('limits search results to the top ten matches', function () {
    $targets = User::factory(12)->create();

    foreach ($targets as $target) {
        $target->forceFill(['name' => 'Alex Result '.$target->id])->save();

        $this->event->participants()->attach($target, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    $response = $this->actingAs($this->viewer)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Alex Result']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Search')
            ->has('results', 10)
        );
});

it('applies the correct authorization rules for search', function (string $actorType) {
    if ($actorType === 'guest') {
        $this->get(route('event.search', ['event' => $this->event, 'q' => 'Lena']))
            ->assertRedirect(route('login'));

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

    $this->actingAs($actor)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Lena']))
        ->assertForbidden();
})->with([
    'guest',
    'outsider',
    'different-event participant',
]);
