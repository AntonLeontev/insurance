<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\FiscalCredential;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Atol\AtolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptReconcileTest extends TestCase
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

    public function test_accountant_and_admin_can_check_and_uncheck_receipt(): void
    {
        foreach ([Role::ACCOUNTANT, Role::ADMIN] as $role) {
            [$agency, $user] = $this->createAgencyWithUser($role);
            $receipt = Receipt::factory()->create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
            ]);

            $checkResponse = $this->actingAs($user)
                ->postJson(route('receipts.check', $receipt));

            $checkResponse->assertOk();
            $checkResponse->assertJsonPath('is_checked', true);
            $checkResponse->assertJsonPath('checked_by.id', $user->id);
            $checkResponse->assertJsonPath('checked_by.name', $user->name);
            $checkResponse->assertJsonPath('checked_by.email', $user->email);

            $receipt->refresh();
            $this->assertTrue($receipt->is_checked);
            $this->assertSame($user->id, $receipt->checked_by_user_id);
            $this->assertNotNull($receipt->checked_at);

            $uncheckResponse = $this->actingAs($user)
                ->postJson(route('receipts.uncheck', $receipt));

            $uncheckResponse->assertOk();
            $uncheckResponse->assertJsonPath('is_checked', false);
            $uncheckResponse->assertJsonPath('checked_by', null);

            $receipt->refresh();
            $this->assertFalse($receipt->is_checked);
            $this->assertNull($receipt->checked_by_user_id);
            $this->assertNull($receipt->checked_at);
        }
    }

    public function test_cashier_and_senior_cashier_cannot_check_or_uncheck(): void
    {
        foreach ([Role::CASHIER, Role::SENIOR_CASHIER] as $role) {
            [$agency, $user] = $this->createAgencyWithUser($role);
            $receipt = Receipt::factory()->create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
            ]);

            $this->actingAs($user)
                ->postJson(route('receipts.check', $receipt))
                ->assertForbidden()
                ->assertJsonPath('message', 'Доступ запрещен');

            $this->actingAs($user)
                ->postJson(route('receipts.uncheck', $receipt))
                ->assertForbidden()
                ->assertJsonPath('message', 'Доступ запрещен');

            $receipt->refresh();
            $this->assertFalse($receipt->is_checked);
            $this->assertNull($receipt->checked_by_user_id);
            $this->assertNull($receipt->checked_at);
        }
    }

    public function test_accountant_cannot_refund_while_admin_and_senior_cashier_can(): void
    {
        [$agency, $accountant] = $this->createAgencyWithUser(Role::ACCOUNTANT);
        $admin = $this->attachUser($agency, Role::ADMIN);
        $seniorCashier = $this->attachUser($agency, Role::SENIOR_CASHIER);

        FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
        ]);

        $accountantReceipt = $this->createDoneReceipt($agency, $accountant);
        $adminReceipt = $this->createDoneReceipt($agency, $admin);
        $seniorReceipt = $this->createDoneReceipt($agency, $seniorCashier);

        $this->mock(AtolService::class)
            ->shouldReceive('sellRefund')
            ->twice()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-refund',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $this->actingAs($accountant)
            ->postJson(route('receipts.refund', $accountantReceipt))
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ запрещен');

        $this->actingAs($admin)
            ->postJson(route('receipts.refund', $adminReceipt))
            ->assertSuccessful();

        $this->actingAs($seniorCashier)
            ->postJson(route('receipts.refund', $seniorReceipt))
            ->assertSuccessful();
    }

    public function test_index_is_checked_filter_returns_only_matching_receipts_of_current_agency(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);
        [$otherAgency, $otherUser] = $this->createAgencyWithUser(Role::ADMIN);

        $checkedReceipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'is_checked' => true,
            'checked_by_user_id' => $user->id,
            'checked_at' => now(),
        ]);
        $uncheckedReceipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'is_checked' => false,
        ]);
        Receipt::factory()->create([
            'agency_id' => $otherAgency->id,
            'user_id' => $otherUser->id,
            'is_checked' => true,
            'checked_by_user_id' => $otherUser->id,
            'checked_at' => now(),
        ]);

        $checkedResponse = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'is_checked', 'value' => 1],
            ],
        ]));

        $checkedResponse->assertOk();
        $checkedIds = collect($checkedResponse->json('data'))->pluck('id');
        $this->assertTrue($checkedIds->contains($checkedReceipt->id));
        $this->assertFalse($checkedIds->contains($uncheckedReceipt->id));
        $this->assertSame(1, $checkedResponse->json('total'));

        $uncheckedResponse = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'is_checked', 'value' => 0],
            ],
        ]));

        $uncheckedResponse->assertOk();
        $uncheckedIds = collect($uncheckedResponse->json('data'))->pluck('id');
        $this->assertTrue($uncheckedIds->contains($uncheckedReceipt->id));
        $this->assertFalse($uncheckedIds->contains($checkedReceipt->id));
        $this->assertSame(1, $uncheckedResponse->json('total'));
    }

    public function test_fiscal_triplet_search_does_not_return_receipt_from_another_agency(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);
        [$otherAgency, $otherUser] = $this->createAgencyWithUser(Role::ADMIN);

        $fiscal = [
            'fn_number' => '9999888877776666',
            'fiscal_document_number' => '123',
            'fiscal_document_attribute' => '4567890123',
        ];

        Receipt::factory()->create([
            'agency_id' => $otherAgency->id,
            'user_id' => $otherUser->id,
            ...$fiscal,
        ]);

        $ownReceipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            ...$fiscal,
        ]);

        $response = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'fn_number', 'value' => $fiscal['fn_number']],
                ['column' => 'fiscal_document_number', 'value' => $fiscal['fiscal_document_number']],
                ['column' => 'fiscal_document_attribute', 'value' => $fiscal['fiscal_document_attribute']],
            ],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertSame($ownReceipt->id, $response->json('data.0.id'));
    }

    public function test_index_can_find_receipt_by_any_single_fiscal_field(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);

        $receipt = Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'fn_number' => '1111222233334444',
            'fiscal_document_number' => '555',
            'fiscal_document_attribute' => '6667778889',
        ]);

        Receipt::factory()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'fn_number' => '9999888877776666',
            'fiscal_document_number' => '111',
            'fiscal_document_attribute' => '2223334445',
        ]);

        foreach ([
            ['column' => 'fn_number', 'value' => '1111222233334444'],
            ['column' => 'fiscal_document_number', 'value' => '555'],
            ['column' => 'fiscal_document_attribute', 'value' => '6667778889'],
        ] as $filter) {
            $response = $this->actingAs($user)->getJson(route('receipts.index', [
                'agency_id' => $agency->id,
                'filters' => [$filter],
            ]));

            $response->assertOk();
            $this->assertSame(1, $response->json('total'));
            $this->assertSame($receipt->id, $response->json('data.0.id'));
        }
    }

    public function test_refund_copy_does_not_inherit_check_fields(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ADMIN);
        FiscalCredential::factory()->create([
            'agency_id' => $agency->id,
            'is_default' => true,
        ]);

        $receipt = $this->createDoneReceipt($agency, $user, [
            'is_checked' => true,
            'checked_by_user_id' => $user->id,
            'checked_at' => now(),
        ]);

        $this->mock(AtolService::class)
            ->shouldReceive('sellRefund')
            ->once()
            ->andReturn((object) [
                'uuid' => 'atol-uuid-refund',
                'status' => ReceiptStatus::DONE->value,
            ]);

        $this->actingAs($user)
            ->postJson(route('receipts.refund', $receipt))
            ->assertSuccessful();

        $refund = Receipt::query()->where('parent_id', $receipt->id)->first();

        $this->assertNotNull($refund);
        $this->assertFalse($refund->is_checked);
        $this->assertNull($refund->checked_by_user_id);
        $this->assertNull($refund->checked_at);

        $receipt->refresh();
        $this->assertTrue($receipt->is_checked);
        $this->assertSame($user->id, $receipt->checked_by_user_id);
    }

    private function attachUser(Agency $agency, Role $role): User
    {
        $user = User::factory()->create();
        $agency->users()->attach($user->id, ['role' => $role->value]);

        return $user;
    }

    private function createDoneReceipt(Agency $agency, User $user, array $overrides = []): Receipt
    {
        return Receipt::factory()->create(array_merge([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'status' => ReceiptStatus::DONE,
            'is_draft' => false,
        ], $overrides));
    }
}
