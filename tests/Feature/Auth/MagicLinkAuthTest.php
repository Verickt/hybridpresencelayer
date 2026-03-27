<?php

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('magic link login page is rendered for onboarding', function () {
    $response = $this->get(route('login'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('canResetPassword', false)
            ->where('canRegister', false),
        );
});

test('sends a magic link email to an existing participant', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => false]);
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $response = $this->post(route('magic-link.send'), [
        'email' => $user->email,
        'event_slug' => $event->slug,
    ]);

    $response->assertOk();
    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('does not reveal whether an email exists for a closed event', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => false]);

    $response = $this->post(route('magic-link.send'), [
        'email' => 'unknown@example.com',
        'event_slug' => $event->slug,
    ]);

    $response->assertOk();
    Notification::assertNothingSent();
    expect(User::where('email', 'unknown@example.com')->exists())->toBeFalse();
});

test('creates a new participant for an open registration event without a password', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => true]);

    $response = $this->post(route('magic-link.send'), [
        'email' => 'new@example.com',
        'event_slug' => $event->slug,
        'name' => 'New Participant',
    ]);

    $response->assertOk();

    $user = User::where('email', 'new@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('New Participant')
        ->and($user->password)->toBeNull();

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('validates magic link requests', function (callable $payloadFactory, array $errors) {
    $event = Event::factory()->create(['allow_open_registration' => true]);

    $payload = array_merge([
        'name' => 'New Participant',
        'email' => 'new@example.com',
        'event_slug' => $event->slug,
    ], $payloadFactory($event));

    $this->from(route('login'))
        ->post(route('magic-link.send'), $payload)
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors($errors);
})->with([
    'missing email' => [
        fn () => ['email' => null],
        ['email'],
    ],
    'invalid email' => [
        fn () => ['email' => 'not-an-email'],
        ['email'],
    ],
    'missing event slug' => [
        fn () => ['event_slug' => null],
        ['event_slug'],
    ],
    'missing name for new open-registration participant' => [
        fn () => ['name' => null],
        ['name'],
    ],
]);

test('reuses the same participant record for repeated open-registration requests', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => true]);
    $email = 'repeat@example.com';

    $this->post(route('magic-link.send'), [
        'name' => 'Repeat User',
        'email' => $email,
        'event_slug' => $event->slug,
    ])->assertOk();

    $this->post(route('magic-link.send'), [
        'name' => 'Repeat User',
        'email' => $email,
        'event_slug' => $event->slug,
    ])->assertOk();

    expect(User::where('email', $email)->count())->toBe(1)
        ->and($event->participants()->where('email', $email)->count())->toBe(1);
});

test('authenticates via a valid magic link', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'a'.str_repeat('b', 63);

    $link = MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
        'used_at' => null,
        'revoked_at' => null,
    ]);

    $response = $this->get(route('magic-link.authenticate', ['token' => $rawToken]));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    expect($link->fresh()->used_at)->not->toBeNull();
});

test('rejects expired magic links', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'c'.str_repeat('d', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->subHour(),
    ]);

    $response = $this->get(route('magic-link.authenticate', ['token' => $rawToken]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('rejects already-used magic links', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'e'.str_repeat('f', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    $response = $this->get(route('magic-link.authenticate', ['token' => $rawToken]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('rejects revoked magic links', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'g'.str_repeat('h', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
        'revoked_at' => now(),
    ]);

    $response = $this->get(route('magic-link.authenticate', ['token' => $rawToken]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('rejects unknown magic links', function () {
    $response = $this->get(route('magic-link.authenticate', ['token' => 'z'.str_repeat('x', 63)]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('rejects a magic link once its real-time expiry passes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 'r'.str_repeat('s', 63);

    MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addMinute(),
    ]);

    $this->travel(2)->minutes();

    $response = $this->get(route('magic-link.authenticate', ['token' => $rawToken]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('cannot authenticate with the same magic link twice', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rawToken = 't'.str_repeat('u', 63);

    $link = MagicLink::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'token_hash' => hash('sha256', $rawToken),
        'expires_at' => now()->addHour(),
    ]);

    $this->get(route('magic-link.authenticate', ['token' => $rawToken]))
        ->assertRedirect(route('dashboard', absolute: false));

    $usedAt = $link->fresh()->used_at;

    auth()->logout();

    $this->get(route('magic-link.authenticate', ['token' => $rawToken]))
        ->assertRedirect(route('login'));

    expect($usedAt)->not->toBeNull()
        ->and($link->fresh()->used_at?->toISOString())->toBe($usedAt?->toISOString());
});

test('rate limits magic link requests', function () {
    $event = Event::factory()->create(['allow_open_registration' => true]);
    $email = 'rate-limited@example.com';

    for ($index = 0; $index < 5; $index++) {
        $this->post(route('magic-link.send'), [
            'email' => $email,
            'event_slug' => $event->slug,
        ]);
    }

    $response = $this->post(route('magic-link.send'), [
        'email' => $email,
        'event_slug' => $event->slug,
    ]);

    $response->assertTooManyRequests();
});
