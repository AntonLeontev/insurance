<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\FiscalCredential;
use App\Models\Insurer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insurer>
 */
class InsurerFactory extends Factory
{
    protected $model = Insurer::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->company(),
            'inn' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'fiscal_credential_id' => null,
        ];
    }

    public function withFiscalCredential(FiscalCredential $credential): static
    {
        return $this->state(fn () => [
            'agency_id' => $credential->agency_id,
            'fiscal_credential_id' => $credential->id,
        ]);
    }
}
