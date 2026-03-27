<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoothThread>
 */
class BoothThreadFactory extends Factory
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
            'user_id' => User::factory(),
            'booth_demo_id' => null,
            'kind' => 'question',
            'body' => $this->faker->sentence().'?',
            'is_answered' => false,
            'is_pinned' => false,
            'follow_up_requested_at' => null,
            'last_activity_at' => now(),
        ];
    }

    public function demoPrompt(?BoothDemo $demo = null): static
    {
        return $this->state(fn () => [
            'booth_demo_id' => $demo?->id ?? BoothDemo::factory(),
            'kind' => 'demo_prompt',
            'body' => 'Live booth demo is happening now. Ask your questions here.',
            'is_pinned' => true,
        ]);
    }
}
