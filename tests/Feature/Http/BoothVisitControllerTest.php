<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create([
        'name' => 'Booth Visitor',
    ]);
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'CyberDefense Booth',
    ]);
});

it('checks into a booth and marks the participant as at the booth', function () {
    $this->actingAs($this->participant)
        ->post(route('event.booths.checkin', [$this->event, $this->booth]))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Checked in');

    expect(BoothVisit::count())->toBe(1);

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('at_booth');
});

it('stores anonymous booth visits without changing the presence state', function () {
    $this->participant->events()->updateExistingPivot($this->event->id, [
        'status' => 'in_session',
        'context_badge' => 'In session: Zero Trust Keynote',
    ]);

    $this->actingAs($this->participant)
        ->post(route('event.booths.checkin', [$this->event, $this->booth]), [
            'anonymous' => true,
        ])
        ->assertSuccessful();

    expect(BoothVisit::count())->toBe(1);
    expect(BoothVisit::first()->is_anonymous)->toBeTrue();

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toBe('In session: Zero Trust Keynote');
});

it('checks out of a booth and resets the presence state', function () {
    BoothVisit::create([
        'user_id' => $this->participant->id,
        'booth_id' => $this->booth->id,
        'is_anonymous' => false,
        'participant_type' => 'physical',
        'entered_at' => now()->subMinutes(12),
    ]);

    $this->actingAs($this->participant)
        ->delete(route('event.booths.checkout', [$this->event, $this->booth]))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Checked out');

    $visit = BoothVisit::first();
    expect($visit->left_at)->not->toBeNull();

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('available')
        ->and($pivot->context_badge)->toBeNull();
});

it('requires authentication for booth check-in and checkout', function () {
    $this->post(route('event.booths.checkin', [$this->event, $this->booth]))
        ->assertRedirect(route('login'));

    $this->delete(route('event.booths.checkout', [$this->event, $this->booth]))
        ->assertRedirect(route('login'));
});
