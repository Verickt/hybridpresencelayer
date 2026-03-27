<?php

use App\Models\Event;
use App\Models\EventSession;
use Carbon\CarbonInterface;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->create(['event_id' => $event->id]);

    expect($session->event->id)->toBe($event->id);
});

it('has a time range', function () {
    $session = EventSession::factory()->create();

    expect($session->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($session->ends_at)->toBeInstanceOf(CarbonInterface::class);
});

it('knows if it is currently happening', function () {
    $live = EventSession::factory()->create([
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addMinutes(50),
    ]);

    $past = EventSession::factory()->create([
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHours(2),
    ]);

    expect($live->isLive())->toBeTrue()
        ->and($past->isLive())->toBeFalse();
});

it('can have Q&A enabled or disabled', function () {
    $session = EventSession::factory()->create([
        'qa_enabled' => false,
        'reactions_enabled' => false,
    ]);

    expect($session->qa_enabled)->toBeFalse()
        ->and($session->reactions_enabled)->toBeFalse();
});

it('keeps the session title and room visible', function () {
    $session = EventSession::factory()->create([
        'title' => 'Zero Trust Keynote',
        'room' => 'Main Stage',
    ]);

    expect($session->title)->toBe('Zero Trust Keynote')
        ->and($session->room)->toBe('Main Stage');
});
