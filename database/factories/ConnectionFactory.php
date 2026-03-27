<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_a_id' => User::factory(),
            'user_b_id' => User::factory(),
            'event_id' => Event::factory(),
            'context' => $this->faker->sentence(),
            'is_cross_world' => $this->faker->boolean(20),
        ];
    }
}
