<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\User;

it('lets booth staff open the lead dashboard without browser errors', function () {
    $event = Event::factory()->live()->create([
        'name' => 'Booth Ops Day',
    ]);
    $staff = User::factory()->create();
    $event->participants()->attach($staff, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Signal Booth',
    ]);
    $booth->staff()->attach($staff);

    $this->actingAs($staff);

    visit(route('event.booths.leads', [$event, $booth], absolute: false))
        ->assertSee('total_visitors')
        ->assertNoSmoke();
});

it('rejects non-staff when they open the lead dashboard in a browser', function () {
    $event = Event::factory()->live()->create();
    $stranger = User::factory()->create();
    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Signal Booth',
    ]);

    $this->actingAs($stranger);

    visit(route('event.booths.leads', [$event, $booth], absolute: false))
        ->assertSee('403');
});
