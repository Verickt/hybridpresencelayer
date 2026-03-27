<?php

use App\Models\Suggestion;

it('expires stale suggestions', function () {
    $stale = Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    $fresh = Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->artisan('suggestions:expire')->assertSuccessful();

    expect($stale->fresh()->status)->toBe('expired')
        ->and($fresh->fresh()->status)->toBe('pending');
});

it('does not touch already resolved suggestions', function () {
    $declined = Suggestion::factory()->create([
        'status' => 'declined',
        'expires_at' => now()->subMinute(),
    ]);

    $accepted = Suggestion::factory()->create([
        'status' => 'accepted',
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('suggestions:expire')->assertSuccessful();

    expect($declined->fresh()->status)->toBe('declined')
        ->and($accepted->fresh()->status)->toBe('accepted');
});

it('does nothing when there are no stale suggestions', function () {
    Suggestion::factory()->count(2)->create([
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->artisan('suggestions:expire')->assertSuccessful();

    expect(Suggestion::where('status', 'expired')->count())->toBe(0);
});
