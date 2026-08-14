<?php

namespace Tests\Feature;

use App\Models\FiscalCredential;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Monolog\Handler\NullHandler;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptClientEmailValidationTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        config([
            'logging.channels.telegram' => ['driver' => 'monolog', 'handler' => NullHandler::class],
            'logging.channels.payments' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }

    public function test_store_rejects_sistema_mart_email_when_insurer_has_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        [, $insurerCredential] = $this->createDefaultAndInsurerCredentials($agency);
        [$insurer, $contract] = $this->createInsurerWithContract($agency, $insurerCredential);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'is_draft' => true,
            'client_email' => 'user@sistema-mart.ru',
        ]);

        $response = $this->actingAs($user)->postJson(route('receipts.store'), $payload);

        $this->assertSistemaMartRejected($response);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_store_allows_sistema_mart_email_when_insurer_has_no_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
        ]);
        [$insurer, $contract] = $this->createInsurerWithContract($agency);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'is_draft' => true,
            'client_email' => 'user@sistema-mart.ru',
        ]);

        $response = $this->actingAs($user)->postJson(route('receipts.store'), $payload);

        $this->assertTrue($response->isSuccessful(), 'Response: '.$response->getContent());
        $this->assertDatabaseHas('receipts', [
            'agency_id' => $agency->id,
            'client_email' => 'user@sistema-mart.ru',
        ]);
    }

    public function test_update_rejects_sistema_mart_email_when_insurer_has_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        [, $insurerCredential] = $this->createDefaultAndInsurerCredentials($agency);
        [$insurer, $contract] = $this->createInsurerWithContract($agency, $insurerCredential);

        $receipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'insurer_id' => $insurer->id,
            'contract_id' => $contract->id,
            'is_draft' => true,
        ]);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'client_email' => 'user@sistema-mart.ru',
        ]);

        $response = $this->actingAs($user)->putJson(route('receipts.update', $receipt), $payload);

        $this->assertSistemaMartRejected($response);
        $this->assertNotSame('user@sistema-mart.ru', $receipt->fresh()->client_email);
    }

    public function test_submit_rejects_sistema_mart_email_when_insurer_has_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        [, $insurerCredential] = $this->createDefaultAndInsurerCredentials($agency);
        [$insurer, $contract] = $this->createInsurerWithContract($agency, $insurerCredential);

        $this->mock(AtolService::class)->shouldNotReceive('sell');

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'client_email' => 'user@sistema-mart.ru',
        ]);

        $response = $this->actingAs($user)->postJson(route('receipts.submit'), $payload);

        $this->assertSistemaMartRejected($response);
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_store_rejects_sistemamart_substring_when_insurer_has_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        [, $insurerCredential] = $this->createDefaultAndInsurerCredentials($agency);
        [$insurer, $contract] = $this->createInsurerWithContract($agency, $insurerCredential);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'is_draft' => true,
            'client_email' => 'client@sistemamart.ru',
        ]);

        $response = $this->actingAs($user)->postJson(route('receipts.store'), $payload);

        $this->assertSistemaMartRejected($response);
        $this->assertDatabaseCount('receipts', 0);
    }

    private function assertSistemaMartRejected(TestResponse $response): void
    {
        $this->assertSame(422, $response->status(), 'Response: '.$response->getContent());
        $response->assertJsonValidationErrors(['client_email']);
        $this->assertSame(
            'Не упоминайте Систему Март в этом поле',
            $response->json('errors.client_email.0'),
            'Response: '.$response->getContent()
        );
    }
}
