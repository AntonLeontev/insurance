<?php

namespace App\Services\Dashboard;

use App\Enums\ReceiptStatus;
use App\Enums\ReceiptType;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Throwable;

class DashboardStats
{
    /**
     * @return array{
     *     days: list<array{date: string, receipts_count: int, sales_sum: string, refunds_sum: string}>,
     *     months: list<array{month: string, sales_sum: string}>,
     *     largest_receipt: array{
     *         id: string,
     *         amount: string,
     *         submited_at: string,
     *         user_name: ?string,
     *         user_email: ?string,
     *         agency_name: ?string,
     *         contract_series: ?string,
     *         contract_number: ?string
     *     }|null,
     *     top_sellers: list<array{user_id: int, name: ?string, email: ?string, sales_sum: string}>
     * }
     */
    public function build(int $agencyId): array
    {
        $now = Carbon::now($this->timezone());
        $rows = $this->receipts($agencyId, $now->copy()->startOfMonth()->subMonths(11));

        $days = $this->emptyDays($now);
        $months = $this->emptyMonths($now);
        $monthStart = $now->copy()->startOfMonth();

        /** @var array<int, int> $sellerKopecks */
        $sellerKopecks = [];
        $largest = null;
        $largestAt = null;

        foreach ($rows as $row) {
            if ($row->submited_at === null) {
                continue;
            }

            $submittedAt = Carbon::parse($row->submited_at, $this->timezone());
            $amount = (int) $row->amount;
            $dayKey = $submittedAt->format('Y-m-d');
            $monthKey = $submittedAt->format('Y-m');
            $isSell = $row->receipt_type === ReceiptType::SELL->value;
            $isRefund = $row->receipt_type === ReceiptType::SELL_REFUND->value;

            if (isset($days[$dayKey])) {
                $days[$dayKey]['receipts_count']++;

                if ($isSell) {
                    $days[$dayKey]['sales_kopecks'] += $amount;
                }

                if ($isRefund) {
                    $days[$dayKey]['refunds_kopecks'] += $amount;
                }
            }

            if ($isSell && isset($months[$monthKey])) {
                $months[$monthKey]['sales_kopecks'] += $amount;
            }

            if (! $isSell || $submittedAt->lt($monthStart)) {
                continue;
            }

            $userId = (int) $row->user_id;
            $sellerKopecks[$userId] = ($sellerKopecks[$userId] ?? 0) + $amount;

            if ($largest === null || $this->isLargerReceipt($amount, $submittedAt, $largest, $largestAt)) {
                $largest = $row;
                $largestAt = $submittedAt;
            }
        }

        $users = $this->users($sellerKopecks, $largest);

        return [
            'days' => $this->presentDays($days),
            'months' => $this->presentMonths($months),
            'largest_receipt' => $this->presentLargest($largest, $largestAt, $agencyId, $users),
            'top_sellers' => $this->presentSellers($sellerKopecks, $users),
        ];
    }

    private function timezone(): string
    {
        return (string) config('app.timezone');
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function receipts(int $agencyId, CarbonInterface $rangeStart): Collection
    {
        try {
            return DB::table('receipts')
                ->select([
                    'id',
                    'user_id',
                    'agency_id',
                    'amount',
                    'receipt_type',
                    'submited_at',
                    'contract_series',
                    'contract_number',
                ])
                ->where('agency_id', $agencyId)
                ->where('is_draft', 0)
                ->where('status', ReceiptStatus::DONE->value)
                ->where('submited_at', '>=', $rangeStart->toDateTimeString())
                ->get();
        } catch (Throwable $exception) {
            Log::error('[DashboardStats.build] query failed', [
                'exception' => [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                ],
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<string, array{date: string, receipts_count: int, sales_kopecks: int, refunds_kopecks: int}>
     */
    private function emptyDays(CarbonInterface $now): array
    {
        $days = [];

        for ($offset = 29; $offset >= 0; $offset--) {
            $date = $now->copy()->startOfDay()->subDays($offset)->format('Y-m-d');
            $days[$date] = [
                'date' => $date,
                'receipts_count' => 0,
                'sales_kopecks' => 0,
                'refunds_kopecks' => 0,
            ];
        }

        return $days;
    }

    /**
     * @return array<string, array{month: string, sales_kopecks: int}>
     */
    private function emptyMonths(CarbonInterface $now): array
    {
        $months = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $month = $now->copy()->startOfMonth()->subMonths($offset)->format('Y-m');
            $months[$month] = [
                'month' => $month,
                'sales_kopecks' => 0,
            ];
        }

        return $months;
    }

    private function isLargerReceipt(int $amount, CarbonInterface $submittedAt, stdClass $current, ?CarbonInterface $currentAt): bool
    {
        $currentAmount = (int) $current->amount;

        if ($amount > $currentAmount) {
            return true;
        }

        if ($amount < $currentAmount || $currentAt === null) {
            return false;
        }

        return $submittedAt->gt($currentAt);
    }

    /**
     * @param  array<int, int>  $sellerKopecks
     * @return Collection<int, stdClass>
     */
    private function users(array $sellerKopecks, ?stdClass $largest): Collection
    {
        $userIds = array_keys($sellerKopecks);

        if ($largest !== null) {
            $userIds[] = (int) $largest->user_id;
        }

        $userIds = array_values(array_unique($userIds));

        if ($userIds === []) {
            return collect();
        }

        return DB::table('users')
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'email'])
            ->keyBy(fn (stdClass $user): int => (int) $user->id);
    }

    /**
     * @param  array<string, array{date: string, receipts_count: int, sales_kopecks: int, refunds_kopecks: int}>  $days
     * @return list<array{date: string, receipts_count: int, sales_sum: string, refunds_sum: string}>
     */
    private function presentDays(array $days): array
    {
        $presented = [];

        foreach ($days as $day) {
            $presented[] = [
                'date' => $day['date'],
                'receipts_count' => $day['receipts_count'],
                'sales_sum' => $this->kopecksToRubles($day['sales_kopecks']),
                'refunds_sum' => $this->kopecksToRubles($day['refunds_kopecks']),
            ];
        }

        return $presented;
    }

    /**
     * @param  array<string, array{month: string, sales_kopecks: int}>  $months
     * @return list<array{month: string, sales_sum: string}>
     */
    private function presentMonths(array $months): array
    {
        $presented = [];

        foreach ($months as $month) {
            $presented[] = [
                'month' => $month['month'],
                'sales_sum' => $this->kopecksToRubles($month['sales_kopecks']),
            ];
        }

        return $presented;
    }

    /**
     * @param  Collection<int, stdClass>  $users
     * @return array{
     *     id: string,
     *     amount: string,
     *     submited_at: string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     agency_name: ?string,
     *     contract_series: ?string,
     *     contract_number: ?string
     * }|null
     */
    private function presentLargest(?stdClass $largest, ?CarbonInterface $submittedAt, int $agencyId, Collection $users): ?array
    {
        if ($largest === null || $submittedAt === null) {
            return null;
        }

        $user = $users->get((int) $largest->user_id);
        $agency = DB::table('agencies')->find($agencyId, ['name']);

        return [
            'id' => (string) $largest->id,
            'amount' => $this->kopecksToRubles((int) $largest->amount),
            'submited_at' => $submittedAt->toIso8601String(),
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'agency_name' => $agency?->name,
            'contract_series' => $largest->contract_series,
            'contract_number' => $largest->contract_number,
        ];
    }

    /**
     * @param  array<int, int>  $sellerKopecks
     * @param  Collection<int, stdClass>  $users
     * @return list<array{user_id: int, name: ?string, email: ?string, sales_sum: string}>
     */
    private function presentSellers(array $sellerKopecks, Collection $users): array
    {
        $sellers = [];

        foreach ($sellerKopecks as $userId => $kopecks) {
            $user = $users->get($userId);
            $sellers[] = [
                'user_id' => $userId,
                'name' => $user?->name,
                'email' => $user?->email,
                'kopecks' => $kopecks,
            ];
        }

        usort($sellers, function (array $left, array $right): int {
            if ($left['kopecks'] !== $right['kopecks']) {
                return $right['kopecks'] <=> $left['kopecks'];
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        $presented = [];

        foreach (array_slice($sellers, 0, 20) as $seller) {
            $presented[] = [
                'user_id' => $seller['user_id'],
                'name' => $seller['name'],
                'email' => $seller['email'],
                'sales_sum' => $this->kopecksToRubles($seller['kopecks']),
            ];
        }

        return $presented;
    }

    private function kopecksToRubles(int $kopecks): string
    {
        return sprintf('%d.%02d', intdiv($kopecks, 100), $kopecks % 100);
    }
}
