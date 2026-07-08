<?php

namespace Tests\Feature;

use App\Models\FiscalCredential;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
