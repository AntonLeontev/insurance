<?php

namespace App\Services\Tbank;

use App\Models\Certificate;

class MerchantApiService
{
    public function __construct(public MerchantApi $merchantApi) {}

    public function initCertificatePayment(Certificate $cert): InitPaymentResponse
    {
        $response = $this->merchantApi
            ->init(
                $cert->sum * 100,
                $cert->id,
                $cert->phone,
                $cert->email,
                "Оплата подарочного сертификата на {$cert->sum} р.",
                route('certificates.payment-success', $cert->id),
                notificationUrl: route('certificates.webhook', $cert)
                // notificationUrl: "https://tunnel.super-anton.ru/certificates/{$cert->id}/webhook"
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
