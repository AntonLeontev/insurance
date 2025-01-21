<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class Digits implements ValidationRule
{
    public array $digitsValues;

    public function __construct(...$digitsValues)
    {
        foreach ($digitsValues as $value) {
            if (! is_int($value)) {
                throw new InvalidArgumentException('All arguments must be integers.');
            }
        }

        $this->digitsValues = $digitsValues;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('Значение :attribute должно быть числом.');
        }

        if (! in_array(mb_strlen((string) $value), $this->digitsValues)) {
            $fail('Количество символов в поле :attribute должно быть '.implode(' или ', $this->digitsValues));
        }
    }
}
