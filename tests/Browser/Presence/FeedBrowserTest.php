<?php

use App\Models\Event;
use App\Models\User;

it('shows only visible participants for the active filters', function () {
    $event = Event::factory()->live()->create();
    $viewer = User::factory()->create();
    $visible = User::factory()->create(['name' => 'Visible Remote']);
    $hidden = User::factory()->create([
        'name' => 'Hidden Remote',
        'is_invisible' => true,
    ]);

    $event->participants()->attach($viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($visible, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $event->participants()->attach($hidden, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->actingAs($viewer);

    visit(route('event.feed', [
        'event' => $event,
        'type' => 'remote',
        'status' => 'available',
    ], absolute: false))
        ->assertPresent('@presence-filters')
        ->assertPresent('@presence-filter-type-remote')
        ->assertPresent('@presence-filter-status-available')
        ->assertPresent('@presence-card-'.$visible->id)
        ->assertMissing('@presence-card-'.$hidden->id)
        ->assertSee('Visible Remote')
        ->assertDontSee('Hidden Remote')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('keeps the feed stable when the user switches to a busy filter', function () {
    $event = Event::factory()->live()->create();
    $viewer = User::factory()->create();
    $busy = User::factory()->create(['name' => 'Busy Participant']);

    $event->participants()->attach($viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($busy, [
        'participant_type' => 'remote',
        'status' => 'busy',
    ]);

    $this->actingAs($viewer);

    visit(route('event.feed', [
        'event' => $event,
        'status' => 'busy',
    ], absolute: false))
        ->assertPresent('@presence-filter-status-busy')
        ->assertPresent('@presence-card-'.$busy->id)
        ->assertSee('Busy Participant')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});
