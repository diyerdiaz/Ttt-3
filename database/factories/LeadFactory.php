<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    private static int $nextPosition = 0;

    /**
     * Positions are unique across the whole factory so (status, position) never collides,
     * even when several leads are made before any is saved.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+57 3## ### ####'),
            'company' => fake()->company(),
            'notes' => fake()->optional()->sentence(),
            'status' => LeadStatus::New,
            'position' => self::$nextPosition++,
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
