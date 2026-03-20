<?php

namespace App\Services\Tbank;

use App\Models\Receipt;
use Carbon\Carbon;

class MerchantApiService
{
    public function __construct(public MerchantApi $merchantApi) {}

    public function initPayment(Receipt $receipt, Carbon $dueDate, int $paymentId): InitPaymentResponse
    {
        $response = $this->merchantApi
            ->init(
                $receipt->amount * 100,
                $paymentId,
                $receipt->client_email,
                "Оплата страхового полиса. Договор {$receipt->contract_series} {$receipt->contract_number}, {$receipt->insurer_name} {$receipt->contract_name}. {$receipt->surname} {$receipt->name} {$receipt->patronymic}",
                $dueDate,
                route('receipts.payment-success', $receipt->id),
                notificationUrl: route('receipts.payment-webhook', $receipt)
                // notificationUrl: "https://tunnel.super-anton.ru/receipts/{$receipt->id}/payment-webhook"
            )
            ->json();

        return InitPaymentResponse::fromArray($response);
    }

    public function getPaymentState(string $paymentId): string
    {
        return $this->merchantApi
            ->getState($paymentId)
            ->json('Status');
    }
}
