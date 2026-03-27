<?php

use App\Models\Event;
use App\Models\Report;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->target = User::factory()->create();

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($this->target, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
});

it('reports a user with a reason', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.report', [$this->event, $this->target]), [
            'reason' => 'Harassment',
        ]);

    $response->assertOk();

    expect(Report::count())->toBe(1)
        ->and(Report::first()->reason)->toBe('Harassment')
        ->and(Report::first()->status)->toBe('pending');
});

it('requires a reason to report', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.report', [$this->event, $this->target]), [
            'reason' => '',
        ]);

    $response->assertSessionHasErrors('reason');
});

it('requires authentication to report', function () {
    $response = $this->post(route('event.report', [$this->event, $this->target]), [
        'reason' => 'Spam',
    ]);

    $response->assertRedirect();
});
