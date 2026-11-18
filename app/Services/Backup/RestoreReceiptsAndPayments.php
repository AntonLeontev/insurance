<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;
use Throwable;

class RestoreReceiptsAndPayments
{
    private const CHUNK_SIZE = 1000;

    private int $insertedReceipts = 0;

    private int $updatedChecks = 0;

    private int $insertedPayments = 0;

    private int $skipped = 0;

    private int $conflicts = 0;

    private int $errors = 0;

    public function run(bool $dryRun = false): int
    {
        try {
            $this->restoreReceipts($dryRun);
            $this->restorePayments($dryRun);
        } catch (Throwable $exception) {
            $this->errors++;

            Log::error('[RestoreReceiptsAndPayments] Не удалось прочитать базу бэкапа', [
                'connection' => 'backup',
                'database' => config('database.connections.backup.database'),
                'exception' => $exception->getMessage(),
            ]);
        }

        return $this->errors > 0 ? 1 : 0;
    }

    /**
     * @return array{inserted_receipts: int, updated_checks: int, inserted_payments: int, skipped: int, conflicts: int, errors: int}
     */
    public function summary(): array
    {
        return [
            'inserted_receipts' => $this->insertedReceipts,
            'updated_checks' => $this->updatedChecks,
            'inserted_payments' => $this->insertedPayments,
            'skipped' => $this->skipped,
            'conflicts' => $this->conflicts,
            'errors' => $this->errors,
        ];
    }

    private function restoreReceipts(bool $dryRun): void
    {
        DB::connection('backup')
            ->table('receipts')
            ->lazyById(self::CHUNK_SIZE, 'id')
            ->each(function (stdClass $row) use ($dryRun): void {
                $this->restoreReceipt($row, $dryRun);
            });
    }

    private function restoreReceipt(stdClass $row, bool $dryRun): void
    {
        try {
            $existing = DB::table('receipts')->where('id', $row->id)->first(['agency_id', 'created_at', 'is_checked']);

            if ($existing === null) {
                $duplicateId = $this->findDuplicateReceiptId($row);

                if ($duplicateId !== null) {
                    $this->conflict('Чек пропущен: в основной базе есть тот же чек с другим id', $row, [
                        'duplicate_receipt_id' => $duplicateId,
                    ]);

                    return;
                }

                $this->insertReceipt($row, $dryRun);

                return;
            }

            if (! $this->isSameReceipt($row, $existing)) {
                $this->conflict('Чек пропущен: id совпадает, но агентство или дата создания отличаются', $row, [
                    'backup_agency_id' => $row->agency_id,
                    'agency_id' => $existing->agency_id,
                    'backup_created_at' => $row->created_at,
                    'created_at' => $existing->created_at,
                ]);

                return;
            }

            if ($this->isChecked($row->is_checked) && ! $this->isChecked($existing->is_checked)) {
                $this->updateCheck($row, $dryRun);

                return;
            }

            $this->skipped++;
        } catch (Throwable $exception) {
            $this->errors++;

            Log::error('[RestoreReceiptsAndPayments.receipts] Не удалось перенести чек', [
                'receipt_id' => $row->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function findDuplicateReceiptId(stdClass $row): ?string
    {
        if (! $this->isBlank($row->external_id)) {
            $id = DB::table('receipts')->where('external_id', $row->external_id)->value('id');

            if ($id !== null) {
                return $id;
            }
        }

        if ($this->isBlank($row->fn_number) || $this->isBlank($row->fiscal_document_number)) {
            return null;
        }

        // Возврат копирует фискальные поля родителя до ответа АТОЛ, поэтому тип чека обязателен в ключе.
        return DB::table('receipts')
            ->where('agency_id', $row->agency_id)
            ->where('receipt_type', $row->receipt_type)
            ->where('fn_number', $row->fn_number)
            ->where('fiscal_document_number', $row->fiscal_document_number)
            ->value('id');
    }

    private function isSameReceipt(stdClass $row, stdClass $existing): bool
    {
        return (string) $row->agency_id === (string) $existing->agency_id
            && (string) $row->created_at === (string) $existing->created_at;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function conflict(string $message, stdClass $row, array $context): void
    {
        $this->conflicts++;

        Log::warning('[RestoreReceiptsAndPayments.receipts] '.$message, [
            'receipt_id' => $row->id,
            ...$context,
        ]);
    }

    private function insertReceipt(stdClass $row, bool $dryRun): void
    {
        if (! $dryRun) {
            DB::table('receipts')->insert(get_object_vars($row));
        }

        $this->insertedReceipts++;
    }

    private function updateCheck(stdClass $row, bool $dryRun): void
    {
        if (! $dryRun) {
            DB::table('receipts')->where('id', $row->id)->update([
                'is_checked' => $row->is_checked,
                'checked_at' => $row->checked_at,
                'checked_by_user_id' => $row->checked_by_user_id,
            ]);
        }

        $this->updatedChecks++;
    }

    private function restorePayments(bool $dryRun): void
    {
        DB::connection('backup')
            ->table('payments')
            ->lazyById(self::CHUNK_SIZE, 'id')
            ->each(function (stdClass $row) use ($dryRun): void {
                $this->restorePayment($row, $dryRun);
            });
    }

    private function restorePayment(stdClass $row, bool $dryRun): void
    {
        try {
            if (! DB::table('receipts')->where('id', $row->receipt_id)->exists()) {
                $this->skipped++;

                Log::warning('[RestoreReceiptsAndPayments.payments] Платёж пропущен: в основной базе нет чека', [
                    'backup_payment_id' => $row->id,
                    'receipt_id' => $row->receipt_id,
                    'payment_id' => $row->payment_id,
                ]);

                return;
            }

            if ($this->paymentExists($row)) {
                $this->skipped++;

                return;
            }

            $this->insertPayment($row, $dryRun);
        } catch (Throwable $exception) {
            $this->errors++;

            Log::error('[RestoreReceiptsAndPayments.payments] Не удалось вставить платёж', [
                'backup_payment_id' => $row->id,
                'receipt_id' => $row->receipt_id,
                'payment_id' => $row->payment_id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function paymentExists(stdClass $row): bool
    {
        if (! $this->isBlank($row->payment_id)) {
            return DB::table('payments')->where('payment_id', $row->payment_id)->exists();
        }

        return DB::table('payments')
            ->where('receipt_id', $row->receipt_id)
            ->where(function ($query): void {
                $query->whereNull('payment_id')->orWhere('payment_id', '');
            })
            ->exists();
    }

    private function insertPayment(stdClass $row, bool $dryRun): void
    {
        if (! $dryRun) {
            DB::table('payments')->insert([
                'receipt_id' => $row->receipt_id,
                'payment_id' => $row->payment_id,
                'status' => $row->status,
                'redirect_url' => $row->redirect_url,
                'paid_at' => $row->paid_at,
                'expired_at' => $row->expired_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $this->insertedPayments++;
    }

    private function isChecked(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
