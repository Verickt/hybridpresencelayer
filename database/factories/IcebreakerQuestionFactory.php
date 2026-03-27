<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class IcebreakerQuestionFactory extends Factory
{
    private static array $questions = [
        "What's the boldest tech bet you've made this year?",
        'What brought you to this event?',
        "What's one thing you hope to learn today?",
        "What's the most underrated technology right now?",
        'If you could solve one problem in your industry, what would it be?',
    ];

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'question' => $this->faker->randomElement(self::$questions),
        ];
    }
}
