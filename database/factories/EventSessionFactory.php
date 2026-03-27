<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventSessionFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('now', '+7 days');

        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'speaker' => $this->faker->name(),
            'room' => 'Room '.$this->faker->randomElement(['A', 'B', 'C', 'Main Stage']),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+45 minutes'),
            'qa_enabled' => true,
            'reactions_enabled' => true,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
        ]);
    }
}
