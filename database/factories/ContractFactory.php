<?php

namespace Database\Factories;

use App\Enums\VatAmount;
use App\Models\Contract;
use App\Models\Insurer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'insurer_id' => Insurer::factory(),
            'name' => fake()->words(2, true),
            'vat' => VatAmount::VAT_20->value,
        ];
    }
}
