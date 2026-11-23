<?php

namespace Database\Factories;

use App\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'project_id' => null,
            'number' => 'CF-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'issue_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+45 days'),
            'status' => InvoiceStatus::Sent,
            'subtotal' => 10000,
            'tax_rate' => 20,
            'tax_amount' => 2000,
            'total' => 12000,
            'currency' => 'MAD',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
