<?php

use App\Events\BoothAnnouncement;
use App\Models\Booth;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventBus;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->staff = User::factory()->create([
        'name' => 'Booth Staff',
    ]);
    $this->event->participants()->attach($this->staff, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Signal Booth',
    ]);
    $this->booth->staff()->attach($this->staff);
});

it('shows the lead dashboard only to booth staff and excludes anonymous visitors', function () {
    $visibleVisitor = User::factory()->create([
        'name' => 'Visible Lead',
        'email' => 'visible@example.test',
    ]);
    $anonymousVisitor = User::factory()->create([
        'name' => 'Anonymous Lead',
        'email' => 'anonymous@example.test',
    ]);

    $this->booth->visits()->create([
        'user_id' => $visibleVisitor->id,
        'is_anonymous' => false,
        'participant_type' => 'remote',
        'entered_at' => now()->subMinutes(20),
    ]);
    $this->booth->visits()->create([
        'user_id' => $anonymousVisitor->id,
        'is_anonymous' => true,
        'participant_type' => 'physical',
        'entered_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($this->staff)
        ->get(route('event.booths.leads', [$this->event, $this->booth]))
        ->assertOk()
        ->assertJsonPath('data.total_visitors', 1)
        ->assertJsonPath('data.physical_count', 0)
        ->assertJsonPath('data.remote_count', 1)
        ->assertJsonPath('data.leads.0.name', 'Visible Lead')
        ->assertJsonMissingPath('data.leads.1');
});

it('rejects non-staff from the lead dashboard', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('event.booths.leads', [$this->event, $this->booth]))
        ->assertForbidden();
});

it('validates booth announcement messages before dispatching them', function () {
    EventBus::fake();

    $this->actingAs($this->staff)
        ->post(route('event.booths.announce', [$this->event, $this->booth]), [
            'message' => str_repeat('x', 281),
        ])
        ->assertSessionHasErrors('message');

    EventBus::assertNotDispatched(BoothAnnouncement::class);
});

it('dispatches booth announcements for staff members', function () {
    EventBus::fake();

    $this->actingAs($this->staff)
        ->post(route('event.booths.announce', [$this->event, $this->booth]), [
            'message' => 'Live demo starting in five minutes.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Announcement sent');

    EventBus::assertDispatched(BoothAnnouncement::class, function (BoothAnnouncement $announcement): bool {
        return $announcement->booth->is($this->booth)
            && $announcement->message === 'Live demo starting in five minutes.';
    });
});

it('exports only visible leads as csv for staff members', function () {
    $visibleVisitor = User::factory()->create([
        'name' => 'CSV Lead',
        'email' => 'csv@example.test',
        'company' => 'CSV AG',
    ]);
    $anonymousVisitor = User::factory()->create([
        'name' => 'Hidden Lead',
        'email' => 'hidden@example.test',
        'company' => 'Hidden AG',
    ]);

    $this->booth->visits()->create([
        'user_id' => $visibleVisitor->id,
        'is_anonymous' => false,
        'participant_type' => 'physical',
        'entered_at' => now()->subMinutes(30),
    ]);
    $this->booth->visits()->create([
        'user_id' => $anonymousVisitor->id,
        'is_anonymous' => true,
        'participant_type' => 'remote',
        'entered_at' => now()->subMinutes(25),
    ]);

    $response = $this->actingAs($this->staff)
        ->get(route('event.booths.leads.export', [$this->event, $this->booth]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())
        ->toContain('Name,Email,Company,Role,Type,"Duration (min)","Visited At"')
        ->toContain('CSV Lead')
        ->not->toContain('Hidden Lead');
});

it('returns not found when a booth from another event is accessed', function () {
    $otherEvent = Event::factory()->live()->create();

    $this->actingAs($this->staff)
        ->get(route('event.booths.leads', [$otherEvent, $this->booth]))
        ->assertNotFound();
});
