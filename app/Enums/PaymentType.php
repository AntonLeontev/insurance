<?php

namespace App\Enums;

enum PaymentType: string
{
    case CASH = 'cash';
    case CASHLESS = 'cashless';

    public function atolType(): int
    {
        return match ($this) {
            self::CASH => 0,
            self::CASHLESS => 1,
        };
    }
}
