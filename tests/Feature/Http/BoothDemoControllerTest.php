<?php

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Acme AI Booth',
    ]);

    $this->staffMember = User::factory()->create();
    $this->participant = User::factory()->create();

    $this->event->participants()->attach($this->staffMember, [
        'participant_type' => 'physical',
        'status' => 'at_booth',
    ]);
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $this->booth->staff()->attach($this->staffMember);
});

it('starts a live booth demo and creates its prompt thread', function () {
    $response = $this->actingAs($this->staffMember)
        ->post(route('event.booths.demos.start', [$this->event, $this->booth]), [
            'title' => 'Live product walkthrough',
        ]);

    $response->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect(BoothDemo::count())->toBe(1)
        ->and(BoothDemo::first()->status)->toBe('live')
        ->and(BoothThread::count())->toBe(1)
        ->and(BoothThread::first()->kind)->toBe('demo_prompt')
        ->and(BoothThread::first()->is_pinned)->toBeTrue();
});

it('forbids non-staff users from starting a booth demo', function () {
    $this->actingAs($this->participant)
        ->post(route('event.booths.demos.start', [$this->event, $this->booth]), [
            'title' => 'This should fail',
        ])
        ->assertForbidden();
});

it('prevents a second live demo from starting while one is active', function () {
    BoothDemo::factory()->create([
        'booth_id' => $this->booth->id,
        'started_by_user_id' => $this->staffMember->id,
        'title' => 'Already live',
        'status' => 'live',
        'starts_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($this->staffMember)
        ->from(route('event.booths.show', [$this->event, $this->booth]))
        ->post(route('event.booths.demos.start', [$this->event, $this->booth]), [
            'title' => 'Another one',
        ])
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]))
        ->assertSessionHasErrors('title');
});

it('ends the current live demo', function () {
    $demo = BoothDemo::factory()->create([
        'booth_id' => $this->booth->id,
        'started_by_user_id' => $this->staffMember->id,
        'title' => 'Live now',
        'status' => 'live',
        'starts_at' => now()->subMinutes(5),
        'ended_at' => null,
    ]);

    $this->actingAs($this->staffMember)
        ->patch(route('event.booths.demos.end', [$this->event, $this->booth, $demo]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect($demo->fresh()->status)->toBe('ended')
        ->and($demo->fresh()->ended_at)->not->toBeNull();
});
