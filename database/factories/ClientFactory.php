<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '+212 6'.fake()->numerify(' ## ## ## ##'),
            'company' => fake()->company(),
            'address' => fake()->streetAddress().', '.fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Agadir']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
