<?php

use App\Jobs\SessionEndedJob;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dispatches SessionEndedJob for participants of recently ended sessions', function () {
    Queue::fake();

    $event = Event::factory()->live()->create();

    $session = EventSession::factory()->create([
        'event_id' => $event->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $event->participants()->attach($userB, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    SessionCheckIn::create([
        'user_id' => $userA->id,
        'event_session_id' => $session->id,
        'created_at' => now()->subHour(),
    ]);

    SessionCheckIn::create([
        'user_id' => $userB->id,
        'event_session_id' => $session->id,
        'created_at' => now()->subHour(),
    ]);

    $this->artisan('matching:post-session')
        ->assertSuccessful();

    Queue::assertPushed(SessionEndedJob::class, fn ($job) => $job->session->id === $session->id);
});

it('ignores sessions that ended more than 15 minutes ago', function () {
    Queue::fake();

    $event = Event::factory()->live()->create();

    $session = EventSession::factory()->create([
        'event_id' => $event->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(20),
    ]);

    $user = User::factory()->create();

    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    SessionCheckIn::create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'created_at' => now()->subHours(2),
    ]);

    $this->artisan('matching:post-session')
        ->assertSuccessful();

    Queue::assertNotPushed(SessionEndedJob::class);
});
