<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_session_id' => EventSession::factory(),
            'body' => $this->faker->sentence().'?',
            'is_answered' => false,
        ];
    }
}
