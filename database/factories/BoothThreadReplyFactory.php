<?php

namespace Database\Factories;

use App\Models\BoothThread;
use App\Models\BoothThreadReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoothThreadReply>
 */
class BoothThreadReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booth_thread_id' => BoothThread::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(),
            'is_staff_answer' => false,
        ];
    }
}
