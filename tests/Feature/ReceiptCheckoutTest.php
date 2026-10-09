<?php

namespace Tests\Feature;

use App\Models\FiscalCredential;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptCheckoutTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        config([
            'logging.channels.payments' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }

    public function test_checkout_returns_400_when_insurer_credential_has_no_terminal(): void
    {
        [$agency] = $this->createAgencyWithUser();

        FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
        ]);

        $insurerCredential = FiscalCredential::factory()->notDefault()->withoutTerminal()->create([
            'agency_id' => $agency->id,
        ]);

        [$insurer] = $this->createInsurerWithContract($agency, $insurerCredential);

        $receipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'insurer_id' => $insurer->id,
            'fiscal_credential_id' => null,
            'is_draft' => true,
        ]);

        $response = $this->postJson(route('receipts.checkout', $receipt));

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Настройки платежной системы не настроены для выбранной страховой',
        ]);
    }

    public function test_index_marks_checkout_unavailable_when_insurer_credential_has_no_terminal(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();

        FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
        ]);

        $insurerCredential = FiscalCredential::factory()->notDefault()->withoutTerminal()->create([
            'agency_id' => $agency->id,
        ]);

        [$insurer] = $this->createInsurerWithContract($agency, $insurerCredential);

        $receipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'insurer_id' => $insurer->id,
            'fiscal_credential_id' => null,
            'is_draft' => true,
        ]);

        $response = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
        ]));

        $response->assertOk();

        $items = collect($response->json('data'));
        $draft = $items->firstWhere('id', $receipt->id);

        $this->assertNotNull($draft);
        $this->assertFalse($draft['checkout_available']);
    }

    public function test_checkout_returns_sbp_marker_when_sbp_only_is_enabled(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        $this->fakeTbankSbp();

        $response = $this->postJson(route('receipts.checkout', $receipt));

        $response->assertOk();
        $response->assertExactJson(['sbp' => true]);
        $this->assertSame(0, Payment::query()->where('receipt_id', $receipt->id)->count());
        Http::assertNothingSent();
    }

    public function test_checkout_returns_redirect_url_when_sbp_only_is_disabled(): void
    {
        $receipt = $this->createPayableDraft();
        $this->fakeTbankSbp();

        $response = $this->postJson(route('receipts.checkout', $receipt));

        $response->assertOk();
        $response->assertJsonPath('redirect_url', 'https://securepay.tinkoff.ru/pay/7000000001');
        $response->assertJsonMissing(['sbp' => true]);
        $this->assertSame(1, Payment::query()->where('receipt_id', $receipt->id)->count());
        $this->assertSame(1, $this->recordedCount('Init'));
    }

    public function test_sbp_qr_returns_svg_and_creates_payment(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        $this->fakeTbankSbp();

        $response = $this->postJson(route('receipts.sbp-qr', $receipt));

        $response->assertOk();
        $response->assertJsonPath('qr_svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $response->assertJsonPath('payment_id', '7000000001');
        $this->assertDatabaseHas('payments', [
            'receipt_id' => $receipt->id,
            'payment_id' => '7000000001',
            'status' => 'NEW',
        ]);
    }

    public function test_sbp_qr_reuses_active_new_payment(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        $this->fakeTbankSbp();

        $this->postJson(route('receipts.sbp-qr', $receipt))->assertOk();
        $this->postJson(route('receipts.sbp-qr', $receipt))->assertOk();

        $this->assertSame(1, Payment::query()->where('receipt_id', $receipt->id)->count());
        $this->assertSame(1, $this->recordedCount('Init'));
        $this->assertSame(2, $this->recordedCount('GetQr'));
    }

    public function test_sbp_banks_are_cached(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        Cache::flush();
        $this->fakeTbankSbp();

        $first = $this->getJson(route('receipts.sbp-banks', [
            'receipt' => $receipt,
            'device' => 'mobile',
        ]));
        $second = $this->getJson(route('receipts.sbp-banks', [
            'receipt' => $receipt,
            'device' => 'mobile',
        ]));

        $first->assertOk();
        $first->assertJsonPath('banks.0.BankId', 'bank-1');
        $first->assertJsonPath('banks.0.BankName', 'Т-Банк');
        $second->assertOk();
        $this->assertSame(1, $this->recordedCount('GetQrBankList'));
    }

    public function test_sbp_deeplink_returns_link_for_active_payment(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        $this->fakeTbankSbp(qrData: 'bank100000000004://qr.nspk.ru/pay');

        Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'status' => 'NEW',
            'payment_id' => '7000000001',
            'expired_at' => now()->addDay(),
        ]);

        $response = $this->postJson(route('receipts.sbp-deeplink', $receipt), [
            'bank_id' => 'bank-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('deeplink', 'bank100000000004://qr.nspk.ru/pay');
    }

    public function test_sbp_deeplink_requires_active_payment(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);

        $response = $this->postJson(route('receipts.sbp-deeplink', $receipt), [
            'bank_id' => 'bank-1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Сначала инициируйте оплату');
    }

    public function test_payment_status_reads_database_without_get_state(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        Http::fake();

        Payment::factory()->create([
            'receipt_id' => $receipt->id,
            'status' => 'NEW',
            'payment_id' => '7000000001',
            'expired_at' => now()->addDay(),
        ]);

        $response = $this->getJson(route('receipts.payment-status', $receipt));

        $response->assertOk();
        $response->assertExactJson([
            'status' => 'NEW',
            'is_draft' => true,
        ]);
        Http::assertNothingSent();
    }

    public function test_sbp_qr_returns_422_when_get_qr_fails(): void
    {
        $receipt = $this->createPayableDraft(sbpOnly: true);
        $this->fakeTbankSbp(qrSuccess: false);

        $response = $this->postJson(route('receipts.sbp-qr', $receipt));

        $response->assertUnprocessable();
        $response->assertJsonPath(
            'message',
            'Не удалось получить QR для оплаты по СБП. Проверьте, что СБП включён на терминале.',
        );
    }

    private function createPayableDraft(bool $sbpOnly = false): Receipt
    {
        [$agency] = $this->createAgencyWithUser();

        $credential = FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
            'terminal' => self::TEST_TERMINAL,
            'password' => self::TEST_PASSWORD,
            'sbp_only' => $sbpOnly,
        ]);

        return Receipt::factory()->create([
            'agency_id' => $agency->id,
            'fiscal_credential_id' => $credential->id,
            'is_draft' => true,
            'client_email' => 'client@example.com',
        ]);
    }

    private function fakeTbankSbp(bool $qrSuccess = true, string $qrData = '<svg xmlns="http://www.w3.org/2000/svg"></svg>'): void
    {
        Http::swap(new Factory);

        Http::fake([
            '*/GetQrBankList' => Http::response([
                'Success' => true,
                'ErrorCode' => 0,
                'BankList' => [[
                    'BankId' => 'bank-1',
                    'NspkBankId' => '100000000004',
                    'BankName' => 'Т-Банк',
                    'BankLogo' => 'https://qr.nspk.ru/proxyapp/logo/bank100000000004.png',
                    'BankOrder' => 1,
                ]],
            ]),
            '*/GetQr' => Http::response([
                'Success' => $qrSuccess,
                'ErrorCode' => $qrSuccess ? 0 : 99,
                'Message' => $qrSuccess ? 'OK' : 'СБП не подключен',
                'Details' => '',
                'Data' => $qrData,
            ]),
            '*/Init' => Http::response([
                'Success' => true,
                'ErrorCode' => 0,
                'TerminalKey' => self::TEST_TERMINAL,
                'Status' => 'NEW',
                'PaymentId' => '7000000001',
                'OrderId' => '1',
                'Amount' => '10000',
                'PaymentURL' => 'https://securepay.tinkoff.ru/pay/7000000001',
            ]),
        ]);
    }

    private function recordedCount(string $endpoint): int
    {
        return collect(Http::recorded())
            ->filter(function (array $pair) use ($endpoint) {
                $path = parse_url($pair[0]->url(), PHP_URL_PATH);

                return is_string($path) && basename($path) === $endpoint;
            })
            ->count();
    }
}
