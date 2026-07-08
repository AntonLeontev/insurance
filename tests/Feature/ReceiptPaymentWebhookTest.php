<?php

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\ReceiptStatus;
use App\Models\Agency;
use App\Models\FiscalCredential;
use App\Models\Insurer;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptPaymentWebhookTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    private const PASSWORD = 'secret-password';

    private const TERMINAL = '1737979662214';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        // Изолируем логирование: канал telegram шлёт в сеть, payments пишет в файл.
        config([
            'logging.channels.telegram' => ['driver' => 'monolog', 'handler' => NullHandler::class],
            'logging.channels.payments' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }

    public function test_returns_400_when_payment_terminal_is_not_configured(): void
    {
        [$receipt] = $this->makeReceiptWithCredential(['terminal' => null, 'password' => null]);

        $payload = $this->webhookPayload();

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertStatus(400);
    }

    public function test_returns_401_when_token_is_missing(): void
    {
        [$receipt] = $this->makeReceiptWithCredential();

        $payload = $this->webhookPayload();
        unset($payload['Token']);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertStatus(401);
        $response->assertSee('Token is required');
    }

    public function test_returns_401_when_token_is_invalid(): void
    {
        [$receipt] = $this->makeReceiptWithCredential();

        $payload = $this->webhookPayload();
        $payload['Token'] = 'invalid-token';

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertStatus(401);
        $response->assertSee('Invalid token');
    }

    public function test_returns_ok_and_does_nothing_when_payment_not_found(): void
    {
        [$receipt] = $this->makeReceiptWithCredential();

        $payload = $this->webhookPayload(['PaymentId' => 999_999_999]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();
        $response->assertSee('OK');

        $this->assertTrue($receipt->fresh()->is_draft);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_is_idempotent_when_payment_already_confirmed(): void
    {
        [$receipt, $credential] = $this->makeReceiptWithCredential();

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'CONFIRMED',
            'paid_at' => now()->subDay(),
        ]);

        $this->mock(AtolService::class)->shouldNotReceive('sell');

        $payload = $this->webhookPayload(['PaymentId' => 8617355556]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();
        $response->assertSee('OK');

        // Чек остался черновиком, платёж не тронут.
        $this->assertTrue($receipt->fresh()->is_draft);
        $this->assertTrue($payment->fresh()->paid_at->equalTo($payment->paid_at));
    }

    public function test_updates_payment_only_for_non_confirmed_status(): void
    {
        [$receipt] = $this->makeReceiptWithCredential();

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'NEW',
        ]);

        $this->mock(AtolService::class)->shouldNotReceive('sell');

        $payload = $this->webhookPayload([
            'PaymentId' => 8617355556,
            'Status' => 'AUTHORIZED',
        ]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();

        $payment->refresh();
        $this->assertSame('AUTHORIZED', $payment->status);
        $this->assertNull($payment->paid_at);

        // Чек не финализирован.
        $this->assertTrue($receipt->fresh()->is_draft);
    }

    public function test_confirmed_draft_finalizes_receipt_and_fiscalizes(): void
    {
        [$receipt, $credential] = $this->makeReceiptWithCredential();

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'NEW',
        ]);

        $this->mock(AtolService::class)
            ->shouldReceive('sell')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-123',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $payload = $this->webhookPayload(['PaymentId' => 8617355556]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();
        $response->assertSee('OK');

        $payment->refresh();
        $this->assertSame('CONFIRMED', $payment->status);
        $this->assertNotNull($payment->paid_at);

        $receipt->refresh();
        $this->assertFalse($receipt->is_draft);
        $this->assertSame(PaymentType::CASHLESS, $receipt->payment_type);
        $this->assertNotNull($receipt->submited_at);
        $this->assertSame($credential->id, $receipt->fiscal_credential_id);
        $this->assertSame($credential->email, $receipt->agent_email);
        $this->assertSame('atol-uuid-123', $receipt->external_id);
        $this->assertSame(ReceiptStatus::DONE, $receipt->status);
    }

    public function test_confirmed_non_draft_updates_payment_without_fiscalization(): void
    {
        [$receipt] = $this->makeReceiptWithCredential(receiptAttrs: ['is_draft' => false]);

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'NEW',
        ]);

        $this->mock(AtolService::class)->shouldNotReceive('sell');

        $payload = $this->webhookPayload(['PaymentId' => 8617355556]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();

        $payment->refresh();
        $this->assertSame('CONFIRMED', $payment->status);
        $this->assertNotNull($payment->paid_at);

        // Блок фискализации пропущен — чек не изменился.
        $receipt->refresh();
        $this->assertFalse($receipt->is_draft);
        $this->assertNull($receipt->external_id);
    }

    public function test_confirmed_webhook_uses_default_credential_when_insurer_has_no_linked_credential(): void
    {
        [$receipt, $defaultCredential] = $this->makeInsurerResolvedReceipt();

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'NEW',
        ]);

        $this->mock(AtolService::class)
            ->shouldReceive('sell')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-default',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $payload = $this->webhookPayload(['PaymentId' => 8617355556]);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();

        $receipt->refresh();
        $this->assertFalse($receipt->is_draft);
        $this->assertSame($defaultCredential->id, $receipt->fiscal_credential_id);
        $this->assertSame($defaultCredential->email, $receipt->agent_email);
        $this->assertSame('CONFIRMED', $payment->fresh()->status);
    }

    public function test_confirmed_webhook_uses_insurer_linked_credential(): void
    {
        $insurerTerminal = '888777666555';
        $insurerPassword = 'insurer-webhook-password';

        [$receipt, $insurerCredential] = $this->makeInsurerResolvedReceipt(
            insurerCredential: FiscalCredential::factory()->notDefault()->create([
                'terminal' => $insurerTerminal,
                'password' => $insurerPassword,
            ]),
        );

        $payment = Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'payment_id' => '8617355556',
            'status' => 'NEW',
        ]);

        $this->mock(AtolService::class)
            ->shouldReceive('sell')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-insurer',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $payload = $this->webhookPayload([
            'PaymentId' => 8617355556,
            'TerminalKey' => $insurerTerminal,
        ], $insurerPassword);

        $response = $this->postJson($this->webhookUrl($receipt), $payload);

        $response->assertOk();

        $receipt->refresh();
        $this->assertFalse($receipt->is_draft);
        $this->assertSame($insurerCredential->id, $receipt->fiscal_credential_id);
        $this->assertSame($insurerCredential->email, $receipt->agent_email);
    }

    /**
     * @return array{0: Receipt, 1: FiscalCredential}
     */
    private function makeReceiptWithCredential(array $credentialAttrs = [], array $receiptAttrs = []): array
    {
        $credential = FiscalCredential::factory()->create(array_merge([
            'terminal' => self::TERMINAL,
            'password' => self::PASSWORD,
        ], $credentialAttrs));

        $receipt = Receipt::factory()->create(array_merge([
            'fiscal_credential_id' => $credential->id,
        ], $receiptAttrs));

        return [$receipt, $credential];
    }

    /**
     * Черновик без fiscal_credential_id — резолв через insurer → default/insurer credential.
     *
     * @return array{0: Receipt, 1: FiscalCredential}
     */
    private function makeInsurerResolvedReceipt(
        ?FiscalCredential $insurerCredential = null,
        array $receiptAttrs = [],
    ): array {
        $agency = Agency::factory()->create();

        $defaultCredential = FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TERMINAL,
            'password' => self::PASSWORD,
        ]);

        if ($insurerCredential !== null) {
            $insurerCredential->update(['agency_id' => $agency->id]);
        }

        $insurerAttributes = ['agency_id' => $agency->id];

        if ($insurerCredential !== null) {
            $insurerAttributes['fiscal_credential_id'] = $insurerCredential->id;
        }

        $insurer = Insurer::factory()->create($insurerAttributes);

        $receipt = Receipt::factory()->create(array_merge([
            'agency_id' => $agency->id,
            'insurer_id' => $insurer->id,
            'fiscal_credential_id' => null,
        ], $receiptAttrs));

        $resolvedCredential = $insurerCredential ?? $defaultCredential;

        return [$receipt, $resolvedCredential];
    }

    private function webhookUrl(Receipt $receipt): string
    {
        return route('receipts.payment-webhook', $receipt);
    }

    private function webhookPayload(array $overrides = [], ?string $password = null): array
    {
        $payload = array_merge([
            'TerminalKey' => self::TERMINAL,
            'OrderId' => '633',
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => 8617355556,
            'ErrorCode' => '0',
            'Amount' => 587849,
            'CardId' => 664911673,
            'Pan' => '220070******7639',
            'ExpDate' => '1235',
        ], $overrides);

        $payload['Token'] = $this->tbankToken($payload, $password ?? self::PASSWORD);

        return $payload;
    }
}
