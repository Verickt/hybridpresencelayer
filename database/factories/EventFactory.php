<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 day', '+7 days');

        return [
            'organizer_id' => User::factory(),
            'name' => $this->faker->words(4, true).' Conference',
            'description' => $this->faker->paragraph(),
            'venue' => $this->faker->address(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+8 hours'),
            'allow_open_registration' => false,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);
    }
}
