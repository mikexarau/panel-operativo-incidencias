<?php

namespace Database\Factories;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(12),
            'status' => IncidentStatus::Open,
            'priority' => IncidentPriority::Medium,
            'requester_name' => $this->faker->name(),
            'assigned_to' => null,
        ];
    }
}
