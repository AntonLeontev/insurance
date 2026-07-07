<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Agency;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'client_email' => fake()->safeEmail(),
            'amount' => fake()->numberBetween(1_000, 100_000),
            'is_draft' => true,
            'payment_type' => PaymentType::CASHLESS->value,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'is_draft' => false,
        ]);
    }
}
