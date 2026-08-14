<?php

namespace Tests\Unit;

use App\Models\FiscalCredential;
use App\Models\Insurer;
use App\Rules\ClientEmailNotSistemaMart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ClientEmailNotSistemaMartTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_for_each_forbidden_substring_when_insurer_has_credential(): void
    {
        $insurer = $this->insurerWithCredential();

        foreach (ClientEmailNotSistemaMart::FORBIDDEN_SUBSTRINGS as $substring) {
            $this->assertRuleFails($insurer->id, "user@{$substring}.ru");
        }
    }

    public function test_fails_case_insensitively_when_insurer_has_credential(): void
    {
        $insurer = $this->insurerWithCredential();

        $this->assertRuleFails($insurer->id, 'SISTEMA-MART@example.com');
    }

    public function test_passes_forbidden_email_when_insurer_has_no_credential(): void
    {
        $insurer = Insurer::factory()->create();

        $this->assertRulePasses($insurer->id, 'client@sistema-mart.ru');
    }

    public function test_passes_forbidden_email_when_insurer_id_is_null(): void
    {
        $this->assertRulePasses(null, 'client@sistema-mart.ru');
    }

    public function test_passes_regular_email_when_insurer_has_credential(): void
    {
        $insurer = $this->insurerWithCredential();

        $this->assertRulePasses($insurer->id, 'client@example.com');
    }

    private function insurerWithCredential(): Insurer
    {
        $credential = FiscalCredential::factory()->create();

        return Insurer::factory()->withFiscalCredential($credential)->create();
    }

    private function assertRuleFails(?int $insurerId, string $email): void
    {
        $validator = Validator::make(
            ['client_email' => $email],
            ['client_email' => [new ClientEmailNotSistemaMart($insurerId)]],
        );

        $this->assertTrue($validator->fails(), "Expected validation to fail for {$email}");
        $this->assertSame(
            'Не упоминайте Систему Март в этом поле',
            $validator->errors()->first('client_email'),
        );
    }

    private function assertRulePasses(?int $insurerId, string $email): void
    {
        $validator = Validator::make(
            ['client_email' => $email],
            ['client_email' => [new ClientEmailNotSistemaMart($insurerId)]],
        );

        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }
}
