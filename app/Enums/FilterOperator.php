<?php

namespace App\Enums;

enum FilterOperator: string
{
    case EQ = '=';
    case NE = '!=';
    case GT = '>';
    case LT = '<';
    case GTE = '>=';
    case LTE = '<=';
}
