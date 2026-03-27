<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

it('lets an authenticated visitor browse booths on mobile', function () {
    $event = Event::factory()->live()->create([
        'name' => 'Booth Day Zurich',
    ]);
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $tag = InterestTag::factory()->create();
    $user->interestTags()->attach($tag, [
        'event_id' => $event->id,
    ]);

    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Signal Booth',
    ]);
    $booth->interestTags()->attach($tag);

    $this->actingAs($user);

    visit(route('event.booths', $event, absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee('Signal Booth')
        ->assertNoSmoke();

    visit(route('event.booths.show', [$event, $booth], absolute: false))
        ->on()->iPhone14Pro()
        ->assertSee('Signal Booth')
        ->assertNoSmoke();
});

it('sends guests to the login page when they open booth pages', function () {
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Signal Booth',
    ]);

    visit(route('event.booths', $event, absolute: false))
        ->assertPathIs('/login')
        ->assertSee('Log in to your account');

    visit(route('event.booths.show', [$event, $booth], absolute: false))
        ->assertPathIs('/login')
        ->assertSee('Log in to your account');
});
