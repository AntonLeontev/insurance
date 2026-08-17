<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesAgencyReceiptContext;
use Tests\TestCase;

class ReceiptIndexDateFilterTest extends TestCase
{
    use CreatesAgencyReceiptContext;
    use RefreshDatabase;

    public function test_index_submited_at_range_includes_boundary_days_and_excludes_outsiders(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);
        [$otherAgency, $otherUser] = $this->createAgencyWithUser(Role::ADMIN);

        $insideStart = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-01 00:00:00');
        $insideEnd = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-17 23:59:59');
        $before = $this->createSubmittedReceipt($agency->id, $user->id, '2026-07-31 23:59:59');
        $after = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-18 00:00:00');
        $otherAgencyInside = $this->createSubmittedReceipt($otherAgency->id, $otherUser->id, '2026-08-10 12:00:00');

        $response = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'submited_at', 'operator' => '>=', 'value' => '2026-08-01'],
                ['column' => 'submited_at', 'operator' => '<=', 'value' => '2026-08-17'],
            ],
        ]));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($insideStart->id));
        $this->assertTrue($ids->contains($insideEnd->id));
        $this->assertFalse($ids->contains($before->id));
        $this->assertFalse($ids->contains($after->id));
        $this->assertFalse($ids->contains($otherAgencyInside->id));
        $this->assertSame(2, $response->json('total'));
    }

    public function test_index_submited_at_from_only_excludes_earlier_receipts(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);

        $earlier = $this->createSubmittedReceipt($agency->id, $user->id, '2026-07-31 23:59:59');
        $onFromDay = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-01 00:00:00');
        $later = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-18 12:00:00');

        $response = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'submited_at', 'operator' => '>=', 'value' => '2026-08-01'],
            ],
        ]));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($earlier->id));
        $this->assertTrue($ids->contains($onFromDay->id));
        $this->assertTrue($ids->contains($later->id));
        $this->assertSame(2, $response->json('total'));
    }

    public function test_index_submited_at_to_only_excludes_later_receipts(): void
    {
        [$agency, $user] = $this->createAgencyWithUser(Role::ACCOUNTANT);

        $earlier = $this->createSubmittedReceipt($agency->id, $user->id, '2026-07-31 12:00:00');
        $onToDay = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-17 23:59:59');
        $later = $this->createSubmittedReceipt($agency->id, $user->id, '2026-08-18 00:00:00');

        $response = $this->actingAs($user)->getJson(route('receipts.index', [
            'agency_id' => $agency->id,
            'filters' => [
                ['column' => 'submited_at', 'operator' => '<=', 'value' => '2026-08-17'],
            ],
        ]));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($earlier->id));
        $this->assertTrue($ids->contains($onToDay->id));
        $this->assertFalse($ids->contains($later->id));
        $this->assertSame(2, $response->json('total'));
    }

    private function createSubmittedReceipt(int $agencyId, int $userId, string $submitedAt): Receipt
    {
        return Receipt::factory()->submitted()->create([
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'submited_at' => Carbon::parse($submitedAt),
        ]);
    }
}
