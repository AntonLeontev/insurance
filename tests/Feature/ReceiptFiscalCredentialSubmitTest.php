<?php

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\ReceiptStatus;
use App\Models\FiscalCredential;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptFiscalCredentialSubmitTest extends TestCase
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

    public function test_submit_uses_default_credential_when_insurer_has_no_linked_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $defaultCredential = FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
        ]);
        [$insurer, $contract] = $this->createInsurerWithContract($agency);

        $this->mock(AtolService::class)
            ->shouldReceive('sell')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-default',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user);

        $response = $this->actingAs($user)->postJson(route('receipts.submit'), $payload);

        $response->assertOk();

        $receipt = Receipt::query()->where('agency_id', $agency->id)->latest('created_at')->first();

        $this->assertNotNull($receipt, 'Response: '.$response->getContent());
        $this->assertFalse($receipt->is_draft);
        $this->assertSame($defaultCredential->id, $receipt->fiscal_credential_id);
        $this->assertSame($defaultCredential->email, $receipt->agent_email);
    }

    public function test_submit_uses_insurer_linked_credential(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        [$defaultCredential, $insurerCredential] = $this->createDefaultAndInsurerCredentials($agency);
        [$insurer, $contract] = $this->createInsurerWithContract($agency, $insurerCredential);

        $this->mock(AtolService::class)
            ->shouldReceive('sell')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-insurer',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $payload = $this->validReceiptPayload($agency, $insurer, $contract, $user, [
            'payment_type' => PaymentType::CASH->value,
        ]);

        $response = $this->actingAs($user)->postJson(route('receipts.submit'), $payload);

        $response->assertOk();

        $this->assertDatabaseCount('receipts', 1);

        $receipt = Receipt::query()->where('agency_id', $agency->id)->latest('created_at')->first();

        $this->assertNotNull($receipt, 'Response: '.$response->getContent());
        $this->assertFalse($receipt->is_draft);
        $this->assertSame($insurerCredential->id, $receipt->fiscal_credential_id);
        $this->assertNotSame($defaultCredential->id, $receipt->fiscal_credential_id);
        $this->assertSame($insurerCredential->email, $receipt->agent_email);
    }
}
