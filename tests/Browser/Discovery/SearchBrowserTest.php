<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\Suggestion;
use App\Models\User;

it('searches participants without exposing invisible results', function () {
    $event = Event::factory()->live()->create();
    $viewer = User::factory()->create();
    $tag = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $visible = User::factory()->create(['name' => 'Lena Fischer']);
    $hidden = User::factory()->create([
        'name' => 'Hidden Person',
        'is_invisible' => true,
    ]);

    $visible->interestTags()->attach($tag, ['event_id' => $event->id]);
    $hidden->interestTags()->attach($tag, ['event_id' => $event->id]);

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

    visit(route('event.search', [
        'event' => $event,
        'q' => 'Lena',
    ], absolute: false))
        ->assertPresent('@search-input')
        ->assertPresent('@search-result-'.$visible->id)
        ->assertMissing('@search-result-'.$hidden->id)
        ->assertSee('Lena Fischer')
        ->assertDontSee('Hidden Person')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('shows the top suggestion result without leaking stale matches', function () {
    $event = Event::factory()->live()->create();
    $viewer = User::factory()->create();
    $suggested = User::factory()->create(['name' => 'Suggested Person']);
    $stale = User::factory()->create(['name' => 'Stale Suggested Person']);

    $event->participants()->attach($viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($suggested, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $event->participants()->attach($stale, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    Suggestion::factory()->create([
        'suggested_to_id' => $viewer->id,
        'suggested_user_id' => $suggested->id,
        'event_id' => $event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(15),
    ]);
    Suggestion::factory()->create([
        'suggested_to_id' => $viewer->id,
        'suggested_user_id' => $stale->id,
        'event_id' => $event->id,
        'status' => 'expired',
        'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($viewer);

    visit(route('event.suggestions', $event, absolute: false))
        ->assertPresent('@suggestion-card-'.$suggested->id)
        ->assertMissing('@suggestion-card-'.$stale->id)
        ->assertSee('Suggested Person')
        ->assertDontSee('Stale Suggested Person')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});
