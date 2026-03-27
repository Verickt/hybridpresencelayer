<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Connection;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\IcebreakerQuestion;
use App\Models\MagicLink;
use App\Models\Message;
use App\Models\Ping;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionReaction;
use App\Models\Suggestion;
use App\Models\User;

function createEventWithData(Event $event): void
{
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booth = Booth::factory()->create(['event_id' => $event->id]);

    Connection::factory()->create(['event_id' => $event->id]);
    Ping::factory()->create(['event_id' => $event->id]);
    Suggestion::factory()->create(['event_id' => $event->id]);
    MagicLink::factory()->create(['event_id' => $event->id]);
    IcebreakerQuestion::factory()->create(['event_id' => $event->id]);

    SessionCheckIn::factory()->create(['event_session_id' => $session->id]);
    SessionReaction::factory()->create(['event_session_id' => $session->id]);
    SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    BoothVisit::factory()->create(['booth_id' => $booth->id]);

    $connection = Connection::factory()->create(['event_id' => $event->id]);
    Message::factory()->create(['connection_id' => $connection->id]);
}

it('purges data for events ended more than 30 days ago', function () {
    $oldEvent = Event::factory()->create([
        'starts_at' => now()->subDays(35),
        'ends_at' => now()->subDays(31),
    ]);

    $recentEvent = Event::factory()->create([
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDay(),
    ]);

    createEventWithData($oldEvent);
    createEventWithData($recentEvent);

    $this->artisan('events:purge-expired')->assertSuccessful();

    // Old event data is purged
    expect($oldEvent->participants()->count())->toBe(0)
        ->and(Connection::where('event_id', $oldEvent->id)->count())->toBe(0)
        ->and(Ping::where('event_id', $oldEvent->id)->count())->toBe(0)
        ->and(Suggestion::where('event_id', $oldEvent->id)->count())->toBe(0)
        ->and(MagicLink::where('event_id', $oldEvent->id)->count())->toBe(0)
        ->and(IcebreakerQuestion::where('event_id', $oldEvent->id)->count())->toBe(0)
        ->and(SessionCheckIn::whereIn('event_session_id', $oldEvent->sessions()->pluck('id'))->count())->toBe(0)
        ->and(SessionReaction::whereIn('event_session_id', $oldEvent->sessions()->pluck('id'))->count())->toBe(0)
        ->and(BoothVisit::whereIn('booth_id', $oldEvent->booths()->pluck('id'))->count())->toBe(0);

    // Old event itself still exists
    expect($oldEvent->fresh())->not->toBeNull();

    // Recent event data is untouched
    expect($recentEvent->participants()->count())->toBe(1)
        ->and(Connection::where('event_id', $recentEvent->id)->count())->toBeGreaterThan(0)
        ->and(Ping::where('event_id', $recentEvent->id)->count())->toBe(1)
        ->and(Suggestion::where('event_id', $recentEvent->id)->count())->toBe(1);
});

it('does nothing when no expired events exist', function () {
    $event = Event::factory()->live()->create();
    createEventWithData($event);

    $this->artisan('events:purge-expired')
        ->assertSuccessful()
        ->expectsOutputToContain('No expired events found');

    expect($event->participants()->count())->toBe(1);
});
