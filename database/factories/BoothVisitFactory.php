<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'booth_id' => Booth::factory(),
            'is_anonymous' => false,
            'participant_type' => $this->faker->randomElement(['physical', 'remote']),
            'entered_at' => now(),
        ];
    }
}
