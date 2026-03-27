<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\EventSession;
use App\Models\User;

it('tracks booth visits with duration', function () {
    $visit = BoothVisit::factory()->create();

    expect($visit->user)->toBeInstanceOf(User::class)
        ->and($visit->booth)->toBeInstanceOf(Booth::class)
        ->and($visit->is_anonymous)->toBeFalse();
});

it('supports anonymous browsing', function () {
    $visit = BoothVisit::factory()->create(['is_anonymous' => true]);

    expect($visit->is_anonymous)->toBeTrue();
});

it('calculates visit duration', function () {
    $visit = BoothVisit::factory()->create([
        'entered_at' => now()->subMinutes(5),
        'left_at' => now(),
    ]);

    expect($visit->durationInMinutes())->toBe(5);
});

it('can record the session a booth visit came from', function () {
    $session = EventSession::factory()->create();
    $visit = BoothVisit::factory()->create(['from_session_id' => $session->id]);

    expect($visit->fromSession->id)->toBe($session->id);
});
