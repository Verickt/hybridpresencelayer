<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->organizer = User::factory()->organizer()->create([
        'name' => 'Organizer One',
    ]);
    $this->event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
    ]);
});

it('creates an event with valid details', function () {
    $response = $this->actingAs($this->organizer)
        ->post(route('events.store'), [
            'name' => 'BSI Conference 2026',
            'description' => 'Cyber security conference',
            'venue' => 'Congress Center Basel',
            'starts_at' => now()->addDays(7)->toISOString(),
            'ends_at' => now()->addDays(7)->addHours(8)->toISOString(),
            'allow_open_registration' => true,
        ]);

    $response->assertRedirect();

    expect(Event::where('name', 'BSI Conference 2026')->exists())->toBeTrue();
});

it('requires authentication before creating an event', function () {
    $this->post(route('events.store'), [
        'name' => 'Guest Event',
        'starts_at' => now()->addDay()->toISOString(),
        'ends_at' => now()->addDays(2)->toISOString(),
    ])->assertRedirect(route('login'));
});

it('forbids non-organizers from creating or updating events', function () {
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->post(route('events.store'), [
            'name' => 'Unauthorized Event',
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDays(2)->toISOString(),
        ])
        ->assertForbidden();

    $this->actingAs($visitor)
        ->patch(route('events.update', $this->event), [
            'name' => 'Unauthorized Update',
            'starts_at' => now()->addDay()->toISOString(),
            'ends_at' => now()->addDays(2)->toISOString(),
        ])
        ->assertForbidden();
});

it('validates event setup payloads when creating events', function (callable $payloadFactory, array $errors) {
    $payload = array_merge([
        'name' => 'Valid Event',
        'description' => 'Useful description',
        'venue' => 'Congress Center Basel',
        'starts_at' => now()->addDays(7)->toISOString(),
        'ends_at' => now()->addDays(7)->addHours(8)->toISOString(),
        'allow_open_registration' => true,
    ], $payloadFactory());

    $this->actingAs($this->organizer)
        ->post(route('events.store'), [
            ...$payload,
        ])
        ->assertSessionHasErrors($errors);
})->with([
    'missing name' => [
        fn () => ['name' => null],
        ['name'],
    ],
    'missing starts_at' => [
        fn () => ['starts_at' => null],
        ['starts_at'],
    ],
    'ends before starts' => [
        fn () => [
            'starts_at' => now()->addDays(2)->toISOString(),
            'ends_at' => now()->addDay()->toISOString(),
        ],
        ['ends_at'],
    ],
]);

it('validates malformed attendee import rows', function (array $row, array $errors) {
    $this->actingAs($this->organizer)
        ->post(route('events.import-attendees', $this->event), [
            'attendees' => [
                array_merge([
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                    'participant_type' => 'physical',
                    'company' => 'Acme',
                    'role_title' => 'Engineer',
                ], $row),
            ],
        ])
        ->assertSessionHasErrors($errors);
})->with([
    'missing email' => [
        ['email' => null],
        ['attendees.0.email'],
    ],
    'invalid email' => [
        ['email' => 'not-an-email'],
        ['attendees.0.email'],
    ],
    'missing participant type' => [
        ['participant_type' => null],
        ['attendees.0.participant_type'],
    ],
    'invalid participant type' => [
        ['participant_type' => 'speaker'],
        ['attendees.0.participant_type'],
    ],
]);

it('deduplicates attendee imports by email', function () {
    $this->actingAs($this->organizer)
        ->post(route('events.import-attendees', $this->event), [
            'attendees' => [
                [
                    'name' => 'Alice',
                    'email' => 'alice@example.test',
                    'participant_type' => 'physical',
                    'company' => 'Acme',
                    'role_title' => 'Engineer',
                ],
                [
                    'name' => 'Alice Duplicate',
                    'email' => 'alice@example.test',
                    'participant_type' => 'remote',
                    'company' => 'Acme',
                    'role_title' => 'Engineer',
                ],
            ],
        ])
        ->assertSuccessful();

    expect($this->event->participants()->where('email', 'alice@example.test')->count())->toBe(1);
});
