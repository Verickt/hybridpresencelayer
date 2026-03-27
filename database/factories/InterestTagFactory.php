<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InterestTagFactory extends Factory
{
    private static array $tags = [
        'Zero Trust', 'Cloud Migration', 'DevOps', 'AI/ML', 'Startup',
        'Enterprise', 'Cybersecurity', 'Data Privacy', 'IoT', 'Blockchain',
        'Remote Work', 'Leadership', 'Open Source', 'Edge Computing', 'API Design',
        'Platform Engineering', 'Observability', 'FinTech', 'HealthTech', 'GreenTech',
    ];

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(self::$tags),
        ];
    }
}
