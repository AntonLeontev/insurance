<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Enums\Sno;
use App\Rules\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AgencyUpdateDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === Role::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'numeric', new Digits(10, 12)],
            'email' => ['required', 'email', 'max:64'],
            'sno' => ['required', 'string', new Enum(Sno::class)],
            'payment_address' => ['required', 'string', 'max:255'],
            'receipt_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название компании',
            'inn' => 'ИНН',
            'email' => 'Email',
            'sno' => 'Система налогооблажения',
            'payment_address' => 'Место расчетов',
            'receipt_email' => 'Email для отправки всех чеков',
        ];
    }
}
