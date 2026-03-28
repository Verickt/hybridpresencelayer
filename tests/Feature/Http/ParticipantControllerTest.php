<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organizer = User::factory()->create();
    $this->event = Event::factory()->live()->create(['organizer_id' => $this->organizer->id]);
    $this->participant = User::factory()->create(['name' => 'Test Participant']);
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('shows participants list to the organizer', function () {
    $this->actingAs($this->organizer)
        ->get(route('event.participants', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Participants')
            ->has('event', fn (Assert $e) => $e
                ->where('id', $this->event->id)
                ->etc()
            )
            ->has('participants', 1)
            ->has('participants.0', fn (Assert $p) => $p
                ->where('id', $this->participant->id)
                ->where('name', 'Test Participant')
                ->has('pivot')
                ->etc()
            )
        );
});

it('denies non-organizer access to participants list', function () {
    $nonOrganizer = User::factory()->create();

    $this->actingAs($nonOrganizer)
        ->get(route('event.participants', $this->event))
        ->assertForbidden();
});

it('allows organizer to delete a participant', function () {
    $this->actingAs($this->organizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->participant]))
        ->assertRedirect(route('event.participants', $this->event));

    $this->assertDatabaseMissing('users', ['id' => $this->participant->id]);
    $this->assertDatabaseMissing('event_user', ['user_id' => $this->participant->id]);
});

it('prevents organizer from deleting themselves', function () {
    $this->event->participants()->attach($this->organizer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->actingAs($this->organizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->organizer]))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->organizer->id]);
});

it('prevents non-organizer from deleting participants', function () {
    $nonOrganizer = User::factory()->create();

    $this->actingAs($nonOrganizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->participant]))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->participant->id]);
});
