<?php

namespace App\Enums;

enum Sno: string
{
    case OSN = 'osn';
    case USN_INCOME = 'usn_income';
    case USN_INCOME_OUTCOME = 'usn_income_outcome';
    case ENVD = 'envd';
    case ESN = 'esn';
    case PATENT = 'patent';

    public function toString(): string
    {
        return match ($this) {
            self::OSN => 'ОСН',
            self::USN_INCOME => 'УСН Доход',
            self::USN_INCOME_OUTCOME => 'УСН Доход - Расход',
            self::ENVD => 'ЕНВД',
            self::ESN => 'ЕСН',
            self::PATENT => 'Патент',
        };
    }
}
