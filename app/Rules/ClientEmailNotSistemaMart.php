<?php

namespace App\Rules;

use App\Models\Insurer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ClientEmailNotSistemaMart implements ValidationRule
{
    public const MESSAGE = 'Не упоминайте Систему Март в этом поле';

    /**
     * @var list<string>
     */
    public const FORBIDDEN_SUBSTRINGS = [
        'sistema-mart',
        'sistemamart',
        'sistema_mart',
    ];

    public function __construct(
        protected ?int $insurerId,
    ) {}

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->insurerId === null) {
            return;
        }

        $insurer = Insurer::query()->find($this->insurerId);

        if ($insurer === null || $insurer->fiscal_credential_id === null) {
            return;
        }

        $email = mb_strtolower((string) $value);

        foreach (self::FORBIDDEN_SUBSTRINGS as $substring) {
            if (str_contains($email, $substring)) {
                $fail(self::MESSAGE);

                return;
            }
        }
    }
}
