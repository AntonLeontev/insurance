<?php

namespace App\Services\Tbank;

readonly class InitPaymentResponse
{
    public function __construct(
        public bool $success,
        public int $errorCode,
        public string $terminalKey,
        public string $status,
        public string|int $paymentId,
        public string|int $orderId,
        public string $amount,
        public string $paymentUrl,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['Success'],
            $data['ErrorCode'],
            $data['TerminalKey'],
            $data['Status'],
            $data['PaymentId'],
            $data['OrderId'],
            $data['Amount'],
            $data['PaymentURL'],
        );
    }
}
