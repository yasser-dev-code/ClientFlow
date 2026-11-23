<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->randomElement(['Refonte e-commerce', 'Application de réservation', 'Campagne digitale', 'Portail client']).' '.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->sentence(12),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'progress' => fake()->numberBetween(0, 100),
            'budget' => fake()->randomFloat(2, 8000, 120000),
            'start_date' => fake()->dateTimeBetween('-4 months', 'now'),
            'deadline' => fake()->dateTimeBetween('now', '+5 months'),
        ];
    }
}
