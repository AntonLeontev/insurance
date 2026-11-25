<?php

namespace App\Services\Kkt;

use App\DTO\AtolExportReceipt;
use App\Enums\ReceiptStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestoreReceiptsFromAtolExport
{
    private int $total = 0;

    private int $inserted = 0;

    private int $matchedByEmail = 0;

    private int $assignedToFallback = 0;

    private int $skippedDuplicate = 0;

    private int $skippedStatus = 0;

    private int $errors = 0;

    private ?string $stopMessage = null;

    public function __construct(private AtolExportReader $reader) {}

    public function run(string $path, int $agencyId, string $fallbackEmail, bool $dryRun): int
    {
        $this->reset();

        $fallbackUserId = $this->fallbackUserId($fallbackEmail);

        if ($fallbackUserId === null) {
            return 1;
        }

        $insertable = $this->collectInsertable($this->reader->read($path));
        $credentialId = $this->credentialId($agencyId, $insertable);

        if ($credentialId === null) {
            return 1;
        }

        $usersByEmail = $this->usersByEmail($insertable);

        foreach ($insertable as $row) {
            $this->restoreRow($row, $agencyId, $credentialId, $fallbackUserId, $usersByEmail, $dryRun);
        }

        return $this->errors > 0 ? 1 : 0;
    }

    /**
     * @return array{
     *     total: int,
     *     inserted: int,
     *     matched_by_email: int,
     *     assigned_to_fallback: int,
     *     skipped_duplicate: int,
     *     skipped_status: int,
     *     errors: int
     * }
     */
    public function summary(): array
    {
        return [
            'total' => $this->total,
            'inserted' => $this->inserted,
            'matched_by_email' => $this->matchedByEmail,
            'assigned_to_fallback' => $this->assignedToFallback,
            'skipped_duplicate' => $this->skippedDuplicate,
            'skipped_status' => $this->skippedStatus,
            'errors' => $this->errors,
        ];
    }

    public function stopMessage(): ?string
    {
        return $this->stopMessage;
    }

    private function reset(): void
    {
        $this->total = 0;
        $this->inserted = 0;
        $this->matchedByEmail = 0;
        $this->assignedToFallback = 0;
        $this->skippedDuplicate = 0;
        $this->skippedStatus = 0;
        $this->errors = 0;
        $this->stopMessage = null;
    }

    private function fallbackUserId(string $fallbackEmail): ?int
    {
        $userId = DB::table('users')
            ->whereRaw('lower(email) = ?', [mb_strtolower($fallbackEmail)])
            ->value('id');

        if ($userId === null) {
            Log::error('[RestoreReceiptsFromAtolExport] Резервный пользователь не найден', [
                'fallback_email' => $fallbackEmail,
            ]);
            $this->stopMessage = 'Резервный пользователь не найден';

            return null;
        }

        return (int) $userId;
    }

    /**
     * @param  list<AtolExportReceipt>  $rows
     * @return list<AtolExportReceipt>
     */
    private function collectInsertable(array $rows): array
    {
        $insertable = [];

        foreach ($rows as $row) {
            $this->total++;

            if ($row->skippedByStatus) {
                $this->skippedStatus++;

                continue;
            }

            if ($row->error !== null) {
                $this->errors++;
                Log::warning('[RestoreReceiptsFromAtolExport] Строка с ошибкой разбора', [
                    'external_id' => $row->externalId,
                    'message' => $row->error,
                ]);

                continue;
            }

            $insertable[] = $row;
        }

        return $insertable;
    }

    /**
     * @param  list<AtolExportReceipt>  $insertable
     */
    private function credentialId(int $agencyId, array $insertable): ?int
    {
        if ($insertable === []) {
            $this->rejectCredentials($agencyId, null, 0);

            return null;
        }

        $groupCode = $insertable[0]->groupCode;
        $credentials = DB::table('fiscal_credentials')
            ->where('agency_id', $agencyId)
            ->where('group_code', $groupCode)
            ->whereNull('deleted_at')
            ->get(['id']);

        if ($credentials->count() === 1) {
            return (int) $credentials->first()->id;
        }

        $this->rejectCredentials($agencyId, $groupCode, $credentials->count());

        return null;
    }

    private function rejectCredentials(int $agencyId, ?string $groupCode, int $count): void
    {
        Log::error('[RestoreReceiptsFromAtolExport] Фискальные реквизиты не найдены или их несколько', [
            'agency_id' => $agencyId,
            'group_code' => $groupCode,
            'count' => $count,
        ]);
        $this->stopMessage = 'Фискальные реквизиты не найдены или их несколько';
    }

    /**
     * @param  list<AtolExportReceipt>  $insertable
     * @return array<string, int>
     */
    private function usersByEmail(array $insertable): array
    {
        $emails = [];

        foreach ($insertable as $row) {
            $email = mb_strtolower((string) $row->clientEmail);

            if ($email !== '') {
                $emails[$email] = $email;
            }
        }

        if ($emails === []) {
            return [];
        }

        $users = DB::table('users')
            ->whereIn(DB::raw('lower(email)'), array_values($emails))
            ->get(['id', 'email']);

        $usersByEmail = [];

        foreach ($users as $user) {
            $usersByEmail[mb_strtolower((string) $user->email)] = (int) $user->id;
        }

        return $usersByEmail;
    }

    /**
     * @param  array<string, int>  $usersByEmail
     */
    private function restoreRow(
        AtolExportReceipt $row,
        int $agencyId,
        int $credentialId,
        int $fallbackUserId,
        array $usersByEmail,
        bool $dryRun,
    ): void {
        $reason = $this->duplicateReason($row);

        if ($reason !== null) {
            $this->skippedDuplicate++;
            Log::warning('[RestoreReceiptsFromAtolExport] Чек пропущен: дубль', [
                'external_id' => $row->externalId,
                'reason' => $reason,
            ]);

            return;
        }

        $email = mb_strtolower((string) $row->clientEmail);

        if ($email !== '' && array_key_exists($email, $usersByEmail)) {
            $this->matchedByEmail++;
            $userId = $usersByEmail[$email];
        } else {
            $this->assignedToFallback++;
            $userId = $fallbackUserId;
        }

        if ($dryRun) {
            return;
        }

        try {
            DB::table('receipts')->insert($this->attributes($row, $agencyId, $credentialId, $userId));
            $this->inserted++;
        } catch (Throwable $exception) {
            $this->errors++;
            Log::error('[RestoreReceiptsFromAtolExport] Не удалось вставить чек', [
                'external_id' => $row->externalId,
                'agency_id' => $agencyId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function duplicateReason(AtolExportReceipt $row): ?string
    {
        if (DB::table('receipts')->where('id', $row->externalId)->exists()) {
            return 'id';
        }

        if ($row->atolUuid !== null && DB::table('receipts')->where('external_id', $row->atolUuid)->exists()) {
            return 'uuid';
        }

        if ($row->fnNumber === null || $row->fiscalDocumentNumber === null || $row->receiptType === null) {
            return null;
        }

        $exists = DB::table('receipts')
            ->where('fn_number', $row->fnNumber)
            ->where('fiscal_document_number', $row->fiscalDocumentNumber)
            ->where('receipt_type', $row->receiptType)
            ->exists();

        if ($exists) {
            return 'fn+fd';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(AtolExportReceipt $row, int $agencyId, int $credentialId, int $userId): array
    {
        $now = now();

        return [
            'id' => $row->externalId,
            'external_id' => $row->atolUuid,
            'agency_id' => $agencyId,
            'fiscal_credential_id' => $credentialId,
            'user_id' => $userId,
            'receipt_type' => $row->receiptType,
            'parent_id' => null,
            'name' => $row->name,
            'surname' => $row->surname,
            'patronymic' => $row->patronymic,
            'passport' => $row->passport,
            'insurer_id' => null,
            'insurer_name' => $row->insurerName,
            'insurer_inn' => $row->insurerInn,
            'contract_id' => null,
            'contract_name' => $row->contractName,
            'vat' => $row->vat,
            'contract_series' => $row->contractSeries,
            'contract_number' => $row->contractNumber,
            'client_email' => $row->clientEmail,
            'agent_email' => $row->agentEmail,
            'amount' => $row->amount,
            'is_draft' => false,
            'payment_type' => $row->paymentType,
            'status' => ReceiptStatus::DONE->value,
            'error_text' => null,
            'fiscal_receipt_number' => null,
            'shift_number' => $row->shiftNumber,
            'receipt_datetime' => $row->receiptDatetime,
            'fn_number' => $row->fnNumber,
            'ecr_registration_number' => $row->ecrRegistrationNumber,
            'fiscal_document_number' => $row->fiscalDocumentNumber,
            'fiscal_document_attribute' => $row->fiscalDocumentAttribute,
            'ofd_receipt_url' => null,
            'submited_at' => $row->submittedAt,
            'is_checked' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
