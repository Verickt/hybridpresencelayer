<?php

use App\Models\Event;
use App\Models\InterestTag;

it('can create an interest tag', function () {
    $tag = InterestTag::factory()->create();

    expect($tag)->toBeInstanceOf(InterestTag::class)
        ->and($tag->name)->toBeString();
});

it('belongs to many events', function () {
    $tag = InterestTag::factory()->create();
    $events = Event::factory()->count(2)->create();

    $tag->events()->attach($events->pluck('id'));

    expect($tag->events)->toHaveCount(2);
});

it('generates slug from name', function () {
    $tag = InterestTag::factory()->create(['name' => 'Zero Trust']);

    expect($tag->slug)->toBe('zero-trust');
});

it('can be attached to the same event only once', function () {
    $tag = InterestTag::factory()->create();
    $event = Event::factory()->create();

    $tag->events()->attach($event->id);
    $tag->events()->syncWithoutDetaching([$event->id]);

    expect($tag->events()->count())->toBe(1);
});
