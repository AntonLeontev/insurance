<?php

namespace App\Enums;

enum ReceiptType: string
{
    case SELL = 'sell';
    case SELL_REFUND = 'sell refund';
}
