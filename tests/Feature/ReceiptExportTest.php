<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Enums\Role;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptExportTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    public function test_admin_can_export_receipts(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ADMIN);
        Receipt::factory()->submitted()->create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'status' => ReceiptStatus::DONE,
        ]);

        $response = $this->actingAs($user)->get(route('receipts.export', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'is_draft', 'value' => 0],
            ],
        ]));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition') ?? '');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition') ?? '');
    }

    public function test_non_admin_cannot_export_receipts(): void
    {
        foreach ([Role::CASHIER, Role::SENIOR_CASHIER, Role::ACCOUNTANT] as $role) {
            [$agency, $user] = $this->createAgencyWithUser($role);

            $this->actingAs($user)
                ->get(route('receipts.export', ['agency_id' => $agency->id]))
                ->assertForbidden();
        }
    }
}
