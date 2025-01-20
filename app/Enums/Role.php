<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case CASHIER = 'cashier';
    case SENIOR_CASHIER = 'senior cashier';

    public function name(): string
    {
        return match ($this) {
            self::ADMIN => 'Администратор',
            self::CASHIER => 'Кассир',
            self::SENIOR_CASHIER => 'Старший кассир',
        };
    }
}
