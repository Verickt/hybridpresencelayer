<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create([
        'name' => 'Scanner User',
    ]);

    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->session = EventSession::factory()->live()->create([
        'event_id' => $this->event->id,
        'title' => 'Zero Trust Keynote',
    ]);

    $this->booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'CyberDefense Booth',
    ]);
});

it('requires authentication for qr resolve requests', function () {
    $this->postJson(route('event.qr.resolve', $this->event), [
        'payload' => '/event/'.$this->event->slug.'/sessions/'.$this->session->id.'/qr-checkin',
    ])->assertUnauthorized();
});

it('rejects qr resolve requests from non-participants', function () {
    $outsider = User::factory()->create();
    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->addMinutes(10),
        ['event' => $this->event, 'session' => $this->session],
        absolute: false,
    );

    $this->actingAs($outsider)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertForbidden();
});

it('validates malformed or external qr payloads', function (string $payload) {
    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertUnprocessable();
})->with([
    'not a url' => 'not-a-url',
    'javascript url' => 'javascript:alert(1)',
    'external url' => 'https://evil.example.com/qr-checkin',
]);

it('rejects expired signed qr payloads', function () {
    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->subMinute(),
        ['event' => $this->event, 'session' => $this->session],
        absolute: false,
    );

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertGone();
});

it('rejects signed qr payloads that belong to another event', function () {
    $foreignEvent = Event::factory()->live()->create();
    $foreignSession = EventSession::factory()->live()->create([
        'event_id' => $foreignEvent->id,
    ]);

    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->addMinutes(10),
        ['event' => $foreignEvent, 'session' => $foreignSession],
        absolute: false,
    );

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertUnprocessable();
});

it('rejects signed payloads that do not map to supported qr actions', function () {
    $payload = URL::temporarySignedRoute(
        'event.feed',
        now()->addMinutes(10),
        ['event' => $this->event],
        absolute: false,
    );

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Unsupported QR action.',
        ]);
});

it('resolves a valid signed session qr payload into a real session check-in', function () {
    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->addMinutes(10),
        ['event' => $this->event, 'session' => $this->session],
        absolute: false,
    );

    $response = $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ]);

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Checked in')
            ->where('action', 'session_check_in')
            ->where('redirect_to', route('event.sessions.show', [$this->event, $this->session], false))
            ->has('target', fn (AssertableJson $target) => $target
                ->where('type', 'session')
                ->where('id', $this->session->id)
                ->where('title', 'Zero Trust Keynote')
                ->etc()
            )
            ->etc()
        );

    expect(SessionCheckIn::count())->toBe(1);

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toContain('Zero Trust Keynote');
});

it('resolves a valid signed booth qr payload into a real booth visit', function () {
    $payload = URL::temporarySignedRoute(
        'event.booths.qr-checkin',
        now()->addMinutes(10),
        ['event' => $this->event, 'booth' => $this->booth],
        absolute: false,
    );

    $response = $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ]);

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Checked in')
            ->where('action', 'booth_check_in')
            ->where('redirect_to', route('event.booths.show', [$this->event, $this->booth], false))
            ->has('target', fn (AssertableJson $target) => $target
                ->where('type', 'booth')
                ->where('id', $this->booth->id)
                ->where('name', 'CyberDefense Booth')
                ->etc()
            )
            ->etc()
        );

    expect(BoothVisit::where('user_id', $this->participant->id)->count())->toBe(1);

    $pivot = $this->participant->events()->where('event_id', $this->event->id)->first()->pivot;

    expect($pivot->status)->toBe('at_booth');
});

it('keeps repeated scans of the same session qr code idempotent', function () {
    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->addMinutes(10),
        ['event' => $this->event, 'session' => $this->session],
        absolute: false,
    );

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertOk();

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), [
            'payload' => $payload,
        ])
        ->assertOk();

    expect(SessionCheckIn::where('user_id', $this->participant->id)
        ->where('event_session_id', $this->session->id)
        ->whereNull('checked_out_at')
        ->count())->toBe(1);
});
