<?php

use App\Events\ParticipantStatusChanged;
use App\Models\Event;
use App\Models\User;
use App\Services\SuggestionService;
use Illuminate\Support\Facades\Event as EventBus;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->organizer = User::factory()->create([
        'name' => 'Organizer One',
    ]);
    $this->event = Event::factory()->live()->create([
        'organizer_id' => $this->organizer->id,
    ]);
});

it('sends an announcement for organizers', function () {
    EventBus::fake();

    $this->actingAs($this->organizer)
        ->post(route('event.actions.announce', $this->event), [
            'message' => 'Networking hour starts now!',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Announcement sent');

    EventBus::assertDispatched(ParticipantStatusChanged::class);
});

it('rejects non-organizers from sending announcements or waves', function () {
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->post(route('event.actions.announce', $this->event), [
            'message' => 'This should fail',
        ])
        ->assertForbidden();

    $this->actingAs($visitor)
        ->post(route('event.actions.serendipity-wave', $this->event))
        ->assertForbidden();
});

it('validates announcement message length', function () {
    $this->actingAs($this->organizer)
        ->post(route('event.actions.announce', $this->event), [
            'message' => str_repeat('x', 501),
        ])
        ->assertSessionHasErrors('message');
});

it('triggers a serendipity wave for eligible participants only', function () {
    $eligibleUsers = User::factory(2)->create();
    $busyUser = User::factory()->create();

    $eligibleUsers->each(function (User $user): void {
        $this->event->participants()->attach($user, [
            'participant_type' => 'physical',
            'status' => 'available',
        ]);
    });

    $this->event->participants()->attach($busyUser, [
        'participant_type' => 'remote',
        'status' => 'busy',
    ]);

    mock(SuggestionService::class, function ($mock): void {
        $mock->shouldReceive('generateForUser')
            ->twice()
            ->andReturn(collect([]));
    });

    $this->actingAs($this->organizer)
        ->post(route('event.actions.serendipity-wave', $this->event))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Serendipity wave triggered')
        ->assertJsonPath('suggestions_generated', 0);
});
