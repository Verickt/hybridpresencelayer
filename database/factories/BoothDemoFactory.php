<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoothDemo>
 */
class BoothDemoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booth_id' => Booth::factory(),
            'started_by_user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'status' => 'live',
            'starts_at' => now(),
            'ended_at' => null,
        ];
    }
}
