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

    public function toString(): string
    {
        return match ($this) {
            self::NONE => 'Без НДС',
            self::VAT_0 => 'НДС 0%',
            self::VAT_10 => 'НДС 10%',
            self::VAT_110 => 'НДС 10/110',
            self::VAT_20 => 'НДС 20%',
            self::VAT_120 => 'НДС 20/120',
        };
    }
}
