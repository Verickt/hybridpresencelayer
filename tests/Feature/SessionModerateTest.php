<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\User;

it('session question has moderation fields', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'is_pinned' => true,
        'is_hidden' => false,
        'answered_by' => $organizer->id,
    ]);

    expect($question->is_pinned)->toBeTrue();
    expect($question->is_hidden)->toBeFalse();
    expect($question->answered_by)->toBe($organizer->id);
    expect($question->answeredByUser)->toBeInstanceOf(User::class);
});
