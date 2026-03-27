<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\SharedInterest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SharedInterest>
 */
class SharedInterestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'event_session_id' => null,
            'booth_id' => null,
            'topic' => $this->faker->sentence(3),
            'expires_at' => now()->addMinutes(30),
        ];
    }
}
