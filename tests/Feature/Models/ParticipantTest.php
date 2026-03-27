<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

it('has participant fields', function () {
    $user = User::factory()->create([
        'company' => 'Acme Corp',
        'role_title' => 'CTO',
        'intent' => 'Looking for cloud migration partners',
        'linkedin_url' => 'https://www.linkedin.com/in/acme-cto',
        'phone' => '+41 44 123 45 67',
        'is_invisible' => true,
    ]);

    expect($user->company)->toBe('Acme Corp')
        ->and($user->role_title)->toBe('CTO')
        ->and($user->intent)->toBe('Looking for cloud migration partners')
        ->and($user->linkedin_url)->toBe('https://www.linkedin.com/in/acme-cto')
        ->and($user->phone)->toBe('+41 44 123 45 67')
        ->and($user->is_invisible)->toBeTrue();
});

it('can be an organizer', function () {
    $user = User::factory()->organizer()->create();

    expect($user->is_organizer)->toBeTrue();
});

it('participates in events with a type', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $user->events()->attach($event, [
        'participant_type' => 'physical',
        'status' => 'available',
        'open_to_call' => true,
        'available_after_session' => false,
    ]);

    $pivot = $user->events->first()->pivot;

    expect($pivot->participant_type)->toBe('physical')
        ->and($pivot->status)->toBe('available')
        ->and((bool) $pivot->open_to_call)->toBeTrue();
});

it('has interest tags scoped to an event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $tag = InterestTag::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $event->id]);

    expect($user->interestTags)->toHaveCount(1)
        ->and($user->interestTags->first()->id)->toBe($tag->id);
});

it('can have an icebreaker answer', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $user->events()->attach($event, [
        'participant_type' => 'remote',
        'status' => 'available',
        'icebreaker_answer' => "What's the boldest tech bet you've made this year?",
    ]);

    expect($user->events->first()->pivot->icebreaker_answer)->toBeString();
});

it('can retrieve events it organizes', function () {
    $organizer = User::factory()->organizer()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    expect($organizer->organizedEvents)->toHaveCount(1)
        ->and($organizer->organizedEvents->first()->id)->toBe($event->id);
});
