<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);

    expect($booth->event->id)->toBe($event->id);
});

it('has staff members', function () {
    $booth = Booth::factory()->create();
    $staff = User::factory()->create();

    $booth->staff()->attach($staff);

    expect($booth->staff)->toHaveCount(1)
        ->and($booth->staff->first()->id)->toBe($staff->id);
});

it('has interest tags', function () {
    $booth = Booth::factory()->create();
    $tag = InterestTag::factory()->create();

    $booth->interestTags()->attach($tag);

    expect($booth->interestTags)->toHaveCount(1);
});

it('casts content links as an array', function () {
    $booth = Booth::factory()->create([
        'content_links' => [
            ['label' => 'Website', 'url' => 'https://example.com'],
        ],
    ]);

    expect($booth->content_links)->toBeArray()
        ->and($booth->content_links[0]['label'])->toBe('Website');
});
