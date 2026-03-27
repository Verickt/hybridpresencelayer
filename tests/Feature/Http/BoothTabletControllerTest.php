<?php

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets booth staff open the tablet console', function () {
    $staffMember = User::factory()->create();
    $participant = User::factory()->create();
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create([
        'event_id' => $event->id,
        'name' => 'Cloudscale Booth',
    ]);
    $event->participants()->attach($staffMember, [
        'participant_type' => 'physical',
        'status' => 'at_booth',
    ]);
    $event->participants()->attach($participant, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $booth->staff()->attach($staffMember);

    $demo = BoothDemo::factory()->create([
        'booth_id' => $booth->id,
        'started_by_user_id' => $staffMember->id,
        'title' => 'Landing zone teardown',
        'status' => 'live',
    ]);
    $promptThread = BoothThread::factory()->create([
        'booth_id' => $booth->id,
        'booth_demo_id' => $demo->id,
        'user_id' => $staffMember->id,
        'kind' => 'demo_prompt',
        'body' => 'Ask how we phase the first rollout.',
        'is_pinned' => true,
    ]);
    $questionThread = BoothThread::factory()->create([
        'booth_id' => $booth->id,
        'user_id' => $participant->id,
        'kind' => 'question',
        'body' => 'Can we get the recap deck after the demo?',
    ]);

    $response = $this->actingAs($staffMember)
        ->get(route('event.booths.tablet', [$event, $booth]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/BoothTablet')
            ->where('booth.name', 'Cloudscale Booth')
            ->where('active_demo.id', $demo->id)
            ->where('active_demo.prompt_thread.id', $promptThread->id)
            ->where('threads.0.id', $questionThread->id)
            ->where('qr.booth_url', route('event.booths.show', [$event, $booth]))
            ->where('qr.svg', fn (string $svg) => str_contains($svg, '<svg'))
            ->where('qr.payload', fn (string $payload) => str_starts_with($payload, "/event/{$event->slug}/booths/{$booth->id}/qr-checkin?"))
            ->etc()
        );
});

it('forbids non-staff users from the booth tablet console', function () {
    $staffMember = User::factory()->create();
    $participant = User::factory()->create();
    $event = Event::factory()->live()->create();
    $booth = Booth::factory()->create([
        'event_id' => $event->id,
    ]);
    $event->participants()->attach($staffMember, [
        'participant_type' => 'physical',
        'status' => 'at_booth',
    ]);
    $event->participants()->attach($participant, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $booth->staff()->attach($staffMember);

    $this->actingAs($participant)
        ->get(route('event.booths.tablet', [$event, $booth]))
        ->assertForbidden();
});
