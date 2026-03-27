<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create([
        'company' => 'CyberDefense AG',
        'role_title' => 'Security Lead',
        'intent' => 'meet founders',
        'is_invisible' => false,
    ]);

    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'quiet',
        'icebreaker_answer' => 'Looking for collaborators',
    ]);
});

test('redirects guests away from event shell pages', function () {
    $this->get(route('event.connections', $this->event))
        ->assertRedirect(route('login'));

    $this->get(route('event.profile', $this->event))
        ->assertRedirect(route('login'));
});

test('shows only the authenticated users connections', function () {
    $otherUser = User::factory()->create([
        'company' => 'Blue Shield',
    ]);

    $this->event->participants()->attach($otherUser, [
        'participant_type' => 'remote',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    Connection::factory()->create([
        'event_id' => $this->event->id,
        'user_a_id' => $this->user->id,
        'user_b_id' => $otherUser->id,
        'context' => 'Met after the keynote',
        'is_cross_world' => true,
    ]);

    $otherEvent = Event::factory()->live()->create();
    $otherConnectionParticipant = User::factory()->create();

    $otherEvent->participants()->attach($otherConnectionParticipant, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);

    Connection::factory()->create([
        'event_id' => $otherEvent->id,
        'user_a_id' => $otherConnectionParticipant->id,
        'user_b_id' => User::factory()->create()->id,
        'context' => 'Different event connection',
        'is_cross_world' => false,
    ]);

    $this->actingAs($this->user)
        ->get(route('event.connections', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Connections')
            ->where('event.slug', $this->event->slug)
            ->has('connections', 1, fn (Assert $connection) => $connection
                ->where('user.id', $otherUser->id)
                ->where('user.name', $otherUser->name)
                ->where('user.company', 'Blue Shield')
                ->where('context', 'Met after the keynote')
                ->where('is_cross_world', true)
                ->etc()
            )
        );
});

test('renders the profile page with safe per-event metadata', function () {
    $interestTag = InterestTag::factory()->create([
        'name' => 'Zero Trust',
    ]);

    $this->user->interestTags()->attach($interestTag->id, [
        'event_id' => $this->event->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('event.profile', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Profile')
            ->where('event.slug', $this->event->slug)
            ->where('user.name', $this->user->name)
            ->where('user.email', $this->user->email)
            ->where('user.company', $this->user->company)
            ->where('user.role_title', $this->user->role_title)
            ->where('user.intent', $this->user->intent)
            ->where('user.participant_type', 'physical')
            ->where('user.status', 'available')
            ->where('user.notification_mode', 'quiet')
            ->where('user.is_invisible', false)
            ->has('interestTags', 1)
            ->missing('user.password')
            ->missing('user.two_factor_secret')
            ->missing('user.two_factor_recovery_codes')
            ->missing('user.remember_token')
            ->etc()
        );
});
