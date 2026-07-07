<?php

namespace Database\Factories;

use App\Enums\Ffd;
use App\Enums\Sno;
use App\Models\Agency;
use App\Models\FiscalCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FiscalCredential>
 */
class FiscalCredentialFactory extends Factory
{
    protected $model = FiscalCredential::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => 'Основные реквизиты',
            'is_default' => true,
            'inn' => (string) fake()->numberBetween(1_000_000_000, 9_999_999_999),
            'sno' => Sno::OSN->value,
            'email' => fake()->safeEmail(),
            'payment_address' => fake()->url(),
            'receipt_email' => fake()->safeEmail(),
            'group_code' => 'test-group',
            'ffd' => Ffd::FFD1_2->value,
            'atol_login' => 'atol-login',
            'atol_password' => 'atol-password',
            'terminal' => (string) fake()->numberBetween(1_000_000_000_000, 9_999_999_999_999),
            'password' => Str::random(16),
        ];
    }

    public function withoutTerminal(): static
    {
        return $this->state(fn () => [
            'terminal' => null,
            'password' => null,
        ]);
    }
}
