<?php

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;

it('can create an event', function () {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->name)->toBeString()
        ->and($event->slug)->toBeString()
        ->and($event->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($event->ends_at)->toBeInstanceOf(CarbonInterface::class);
});

it('belongs to an organizer', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    expect($event->organizer)->toBeInstanceOf(User::class)
        ->and($event->organizer->id)->toBe($organizer->id);
});

it('generates a slug from the name', function () {
    $event = Event::factory()->create(['name' => 'BSI Cyber Security Conference 2026']);

    expect($event->slug)->toBe('bsi-cyber-security-conference-2026');
});

it('casts date and registration fields', function () {
    $event = Event::factory()->create(['allow_open_registration' => true]);

    expect($event->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($event->ends_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($event->allow_open_registration)->toBeTrue();
});

it('knows if it is currently live', function () {
    $live = Event::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $past = Event::factory()->create([
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    expect($live->isLive())->toBeTrue()
        ->and($past->isLive())->toBeFalse();
});
