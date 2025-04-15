<?php

namespace App\Enums;

use App\Services\Atol\Enums\ApiVersion;

enum Ffd: string
{
    case FFD1_2 = 'ffd1.2';
    case FFD1_05 = 'ffd1.05';

    public function apiVersion(): ApiVersion
    {
        return match ($this) {
            self::FFD1_05 => ApiVersion::V4,
            self::FFD1_2 => ApiVersion::V5,
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::FFD1_05 => '1.05',
            self::FFD1_2 => '1.2',
        };
    }
}
