<?php

namespace App\Enums;

enum ReceiptType: string
{
    case SELL = 'sell';
    case SELL_REFUND = 'sell refund';

    public function label(): string
    {
        return match ($this) {
            self::SELL => 'Приход',
            self::SELL_REFUND => 'Возврат',
        };
    }
}
