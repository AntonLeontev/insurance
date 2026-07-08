<?php

namespace Tests\Concerns;

use App\Enums\PaymentType;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Contract;
use App\Models\FiscalCredential;
use App\Models\Insurer;
use App\Models\User;

trait CreatesAgencyReceiptContext
{
    protected const TEST_TERMINAL = '1737979662214';

    protected const TEST_PASSWORD = 'secret-password';

    /**
     * @return array{0: Agency, 1: User}
     */
    protected function createAgencyWithUser(Role $role = Role::ADMIN): array
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create();
        $agency->users()->attach($user->id, ['role' => $role->value]);

        return [$agency, $user];
    }

    /**
     * @return array{0: Insurer, 1: Contract}
     */
    protected function createInsurerWithContract(Agency $agency, ?FiscalCredential $credential = null): array
    {
        $insurerAttributes = [
            'agency_id' => $agency->id,
        ];

        if ($credential !== null) {
            $insurerAttributes['fiscal_credential_id'] = $credential->id;
        }

        $insurer = Insurer::factory()->create($insurerAttributes);
        $contract = Contract::factory()->create([
            'insurer_id' => $insurer->id,
        ]);

        return [$insurer, $contract];
    }

    protected function validReceiptPayload(Agency $agency, Insurer $insurer, Contract $contract, User $user, array $overrides = []): array
    {
        return array_merge([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'name' => 'Иван',
            'surname' => 'Иванов',
            'patronymic' => 'Иванович',
            'passport' => '1234 567890',
            'insurer_id' => $insurer->id,
            'contract_id' => $contract->id,
            'contract_series' => 'АА',
            'contract_number' => '12345',
            'client_email' => 'client@example.com',
            'agent_email' => 'agent@example.com',
            'amount' => 10000,
            'is_draft' => false,
            'payment_type' => PaymentType::CASH->value,
        ], $overrides);
    }

    /**
     * @return array{0: FiscalCredential, 1: FiscalCredential}
     */
    protected function createDefaultAndInsurerCredentials(Agency $agency): array
    {
        $defaultCredential = FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
        ]);

        $insurerCredential = FiscalCredential::factory()->notDefault()->create([
            'agency_id' => $agency->id,
            'terminal' => '999888777666',
            'password' => 'insurer-password',
        ]);

        return [$defaultCredential, $insurerCredential];
    }

    /**
     * Повторяет алгоритм App\Services\Tbank\MerchantApi::makeToken/verifyWebhookToken.
     */
    protected function tbankToken(array $payload, string $password): string
    {
        $data = [];

        foreach ($payload as $key => $value) {
            if ($key === 'Token') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $data[$key] = $value;
        }

        $data['Password'] = $password;
        ksort($data);

        $concatenated = '';
        foreach ($data as $value) {
            if (! is_array($value)) {
                $concatenated .= $value;
            }
        }

        return hash('sha256', $concatenated);
    }
}
