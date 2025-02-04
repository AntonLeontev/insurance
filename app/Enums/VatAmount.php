<?php

namespace App\Enums;

enum VatAmount: string
{
    case NONE = 'none';
    case VAT_0 = 'vat0';
    case VAT_10 = 'vat10';
    case VAT_110 = 'vat110';
    case VAT_20 = 'vat20';
    case VAT_120 = 'vat120';
}
