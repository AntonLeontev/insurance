<?php

namespace App\DTO;

readonly class AtolExportReceipt
{
    public function __construct(
        public int $rowNumber,
        public ?string $externalId,
        public ?string $atolUuid,
        public ?string $groupCode,
        public bool $skippedByStatus,
        public ?string $error,
        public ?string $receiptType = null,
        public ?string $surname = null,
        public ?string $name = null,
        public ?string $patronymic = null,
        public ?string $passport = null,
        public ?string $insurerName = null,
        public ?string $insurerInn = null,
        public ?string $contractName = null,
        public ?string $contractSeries = null,
        public ?string $contractNumber = null,
        public ?string $vat = null,
        public ?string $clientEmail = null,
        public ?string $agentEmail = null,
        public ?int $amount = null,
        public ?string $paymentType = null,
        public ?string $shiftNumber = null,
        public ?string $receiptDatetime = null,
        public ?string $fnNumber = null,
        public ?string $ecrRegistrationNumber = null,
        public ?string $fiscalDocumentNumber = null,
        public ?string $fiscalDocumentAttribute = null,
        public ?string $submittedAt = null,
    ) {}

    public function isInsertable(): bool
    {
        return ! $this->skippedByStatus && $this->error === null;
    }
}
