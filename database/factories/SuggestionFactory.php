<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'suggested_to_id' => User::factory(),
            'suggested_user_id' => User::factory(),
            'event_id' => Event::factory(),
            'score' => $this->faker->randomFloat(2, 0, 1),
            'reason' => 'Shares 2 interest tags',
            'status' => 'pending',
            'trigger' => 'interest_overlap',
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'status' => 'declined',
        ]);
    }
}
