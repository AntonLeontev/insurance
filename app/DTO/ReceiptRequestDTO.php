<?php

namespace App\DTO;

use App\Enums\PaymentType;
use App\Enums\Sno;
use App\Models\Agency;
use App\Models\Receipt;
use App\Services\Atol\Enums\ApiVersion;
use Carbon\Carbon;
use JsonSerializable;

readonly class ReceiptRequestDTO implements JsonSerializable
{
    public function __construct(
        public ApiVersion $version,
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

    public static function fromReceipt(Receipt $receipt, Agency $agency, ApiVersion $version): static
    {
        return new static(
            $version,
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

        $items = [
            [
                'name' => "Договор страхования {$this->contractSeries} {$this->contractNumber}, вид страхования – {$this->contractName}",
                'price' => $this->amount,
                'quantity' => 1,
                'sum' => $this->amount,
                'payment_method' => 'full_payment',
                'agent_info' => ['type' => 'commission_agent'],
                'supplier_info' => [
                    'name' => $this->insurerName,
                    'inn' => $this->insurerInn,
                ],
                'vat' => ['type' => 'none'],
            ],
        ];

        if ($this->version === ApiVersion::V4) {
            $items[0]['measurement_unit'] = 'Полис';
            $items[0]['payment_object'] = 'service';
        }
        if ($this->version === ApiVersion::V5) {
            $items[0]['measure'] = 0;
            $items[0]['payment_object'] = 4;
        }

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
                'items' => $items,
                'payments' => [[
                    'type' => $this->paymentType->atolType(),
                    'sum' => $this->amount,
                ]],
                'total' => $this->amount,
            ],
            'service' => [
                'callback_url' => config('app.url') === 'http://127.0.0.1:8000' ? 'http://45.146.165.254:8080/webhooks/atol' : route('webhooks.atol'),
            ],
            'timestamp' => $this->submittedAt->format('d.m.Y H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
