<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->company().' Booth',
            'company' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'content_links' => [
                ['label' => 'Website', 'url' => $this->faker->url()],
                ['label' => 'Product Sheet', 'url' => $this->faker->url()],
            ],
        ];
    }
}
