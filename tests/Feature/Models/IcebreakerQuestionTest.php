<?php

use App\Models\Event;
use App\Models\IcebreakerQuestion;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $question = IcebreakerQuestion::factory()->create(['event_id' => $event->id]);

    expect($question->event->id)->toBe($event->id)
        ->and($question->question)->toBeString();
});

it('stores the question text verbatim', function () {
    $question = IcebreakerQuestion::factory()->create([
        'question' => "What's one thing you hope to learn today?",
    ]);

    expect($question->question)->toBe("What's one thing you hope to learn today?");
});
