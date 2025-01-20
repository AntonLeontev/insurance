<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case CASHIER = 'cashier';
    case SENIOR_CASHIER = 'senior cashier';
}
