<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reported_id' => User::factory(),
            'event_id' => Event::factory(),
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
        ];
    }
}
