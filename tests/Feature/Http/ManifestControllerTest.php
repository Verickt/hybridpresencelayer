<?php

use App\Models\Event;
use Illuminate\Support\Str;

test('returns a no-store manifest for the requested event', function () {
    $event = Event::factory()->create([
        'name' => 'Hybrid Presence Summit 2026',
        'description' => 'A conference for hybrid event networking.',
        'theme_color' => '#123456',
    ]);

    $this->get(route('event.manifest', $event))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJson([
            'name' => $event->name,
            'short_name' => Str::substr($event->name, 0, 12),
            'description' => $event->description,
            'start_url' => route('event.feed', $event),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#123456',
            'orientation' => 'portrait',
        ])
        ->assertJsonCount(2, 'icons');
});

test('returns 404 for an unknown event slug', function () {
    $this->get('/event/not-a-real-event/manifest.json')
        ->assertNotFound();
});

test('builds a start url that stays scoped to the event feed', function () {
    $event = Event::factory()->create();

    $response = $this->get(route('event.manifest', $event));

    $response->assertOk();
    expect($response->json('start_url'))->toBe(route('event.feed', $event));
});
