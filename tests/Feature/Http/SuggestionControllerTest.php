<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->viewer = User::factory()->create();
    $tag = InterestTag::factory()->create();

    $this->viewer->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($this->viewer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $candidate = User::factory()->create();
    $candidate->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($candidate, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $this->suggestion = Suggestion::factory()->create([
        'suggested_to_id' => $this->viewer->id,
        'suggested_user_id' => $candidate->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(15),
    ]);
});

it('returns suggestions for the current user', function () {
    $response = $this->actingAs($this->viewer)
        ->get(route('event.suggestions', $this->event));

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.0', fn (AssertableJson $suggestion) => $suggestion
                ->where('id', $this->suggestion->id)
                ->has('suggested_user', fn (AssertableJson $user) => $user
                    ->where('id', $this->suggestion->suggested_user_id)
                    ->missing('email')
                    ->missing('password')
                    ->missing('remember_token')
                    ->etc()
                )
                ->whereType('score', 'double')
                ->whereType('reason', 'string')
                ->whereType('expires_at', 'string')
                ->etc()
            )
        );
});

it('returns an empty payload when the user has no active suggestions', function () {
    Suggestion::query()->delete();

    $response = $this->actingAs($this->viewer)
        ->get(route('event.suggestions', $this->event));

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('declines a suggestion', function () {
    $response = $this->actingAs($this->viewer)
        ->patch(route('event.suggestions.decline', [$this->event, $this->suggestion]));

    $response->assertOk();

    expect($this->suggestion->fresh()->status)->toBe('declined');
});

it('accepts a suggestion', function () {
    $response = $this->actingAs($this->viewer)
        ->patch(route('event.suggestions.accept', [$this->event, $this->suggestion]));

    $response->assertOk();

    expect($this->suggestion->fresh()->status)->toBe('accepted');
});

it('rejects accepting stale suggestions', function () {
    $this->suggestion->update([
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($this->viewer)
        ->patch(route('event.suggestions.accept', [$this->event, $this->suggestion]));

    $response->assertConflict();

    expect($this->suggestion->fresh()->status)->not->toBe('accepted');
});

it('rejects changing suggestions that are already handled', function (string $status, string $actionRoute) {
    $this->suggestion->update([
        'status' => $status,
    ]);

    $response = $this->actingAs($this->viewer)
        ->patch(route($actionRoute, [$this->event, $this->suggestion]));

    $response->assertConflict();

    expect($this->suggestion->fresh()->status)->toBe($status);
})->with([
    'accepted suggestion cannot be declined again' => ['accepted', 'event.suggestions.decline'],
    'declined suggestion cannot be accepted again' => ['declined', 'event.suggestions.accept'],
]);

it('rejects changing another users suggestion', function () {
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->patch(route('event.suggestions.accept', [$this->event, $this->suggestion]));

    $response->assertForbidden();
});

it('hides expired suggestions from the index', function () {
    Suggestion::factory()->create([
        'suggested_to_id' => $this->viewer->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('event.suggestions', $this->event));

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('applies the correct authorization rules for suggestion endpoints', function (string $actorType) {
    if ($actorType === 'guest') {
        $this->get(route('event.suggestions', $this->event))
            ->assertRedirect(route('login'));

        return;
    }

    $actor = User::factory()->create();

    if ($actorType === 'different-event participant') {
        $otherEvent = Event::factory()->live()->create();
        $otherEvent->participants()->attach($actor, [
            'participant_type' => 'remote',
            'status' => 'available',
        ]);
    }

    $this->actingAs($actor)
        ->patch(route('event.suggestions.accept', [$this->event, $this->suggestion]))
        ->assertForbidden();
})->with([
    'guest',
    'outsider',
    'different-event participant',
]);
