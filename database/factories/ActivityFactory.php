<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\Models\Activity;
use App\Domain\Leads\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'type' => fake()->randomElement(ActivityType::cases()),
            'description' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
