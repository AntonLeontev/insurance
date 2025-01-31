<?php

namespace App\DTO;

use App\Enums\PaymentType;
use App\Enums\Sno;
use App\Models\Agency;
use App\Models\Receipt;
use Carbon\Carbon;
use JsonSerializable;

readonly class ReceiptRequestDTO implements JsonSerializable
{
    public function __construct(
        public string $externalId,
        public string $clientName,
        public string $clientSurname,
        public ?string $clientPatronymic,
        public string $clientPassport,
        public string $clientEmail,
        public string $agentEmail,
        public Sno $sno,
        public string $inn,
        public string $paymentAddress,
        public string $contractName,
        public string $contractSeries,
        public string $contractNumber,
        public float|int $amount,
        public string $insurerName,
        public string $insurerInn,
        public PaymentType $paymentType,
        public Carbon $submittedAt,
    ) {}

    public static function fromReceipt(Receipt $receipt, Agency $agency): static
    {
        return new static(
            $receipt->id,
            $receipt->name,
            $receipt->surname,
            $receipt->patronymic,
            $receipt->passport,
            $receipt->client_email,
            $receipt->agent_email,
            $agency->sno,
            $agency->inn,
            $agency->payment_address,
            $receipt->contract_name,
            $receipt->contract_series,
            $receipt->contract_number,
            $receipt->amount,
            $receipt->insurer_name,
            $receipt->insurer_inn,
            $receipt->payment_type,
            $receipt->submited_at,
        );
    }

    public function toArray(): array
    {
        $clientName = "{$this->clientSurname} {$this->clientName}";
        $clientName .= $this->clientPatronymic
            ? " {$this->clientPatronymic} {$this->clientPassport}"
            : " {$this->clientPassport}";

        return [
            'external_id' => $this->externalId,
            'receipt' => [
                'client' => [
                    'email' => $this->clientEmail,
                    'name' => $clientName,
                ],
                'company' => [
                    'email' => $this->agentEmail,
                    'sno' => $this->sno->value,
                    'inn' => $this->inn,
                    'payment_address' => $this->paymentAddress,
                ],
                'items' => [
                    [
                        'name' => "Договор страхования {$this->contractSeries} {$this->contractNumber}, вид страхования – {$this->contractName}",
                        'price' => $this->amount,
                        'quantity' => 1,
                        'sum' => $this->amount,
                        'measurement_unit' => 'Полис',
                        'payment_method' => 'full_payment',
                        'payment_object' => 'service',
                        'agent_info' => ['type' => 'commission_agent'],
                        'supplier_info' => [
                            'name' => $this->insurerName,
                            'inn' => $this->insurerInn,
                        ],
                        'vat' => ['type' => 'none'],
                    ],
                ],
                'payments' => [[
                    'type' => $this->paymentType->atolType(),
                    'sum' => $this->amount,
                ]],
                'total' => $this->amount,
            ],
            'service' => [
                'callback_url' => 'http://45.146.165.254:8080/webhooks/atol',
            ],
            'timestamp' => $this->submittedAt->format('d.m.Y H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
