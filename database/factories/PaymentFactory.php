<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'receipt_id' => Receipt::factory(),
            'payment_id' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'status' => 'NEW',
            'expired_at' => now()->addDays(7),
        ];
    }
}
