<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'recorded_by' => null,
            'amount' => 3000,
            'paid_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'method' => PaymentMethod::BankTransfer,
            'reference' => fake()->optional()->bothify('VIR-####-????'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
