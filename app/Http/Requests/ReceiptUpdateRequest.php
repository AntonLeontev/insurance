<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReceiptUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->agency_id === $this->get('agency_id');
    }

    public function rules(): array
    {
        return [
            'agency_id' => ['required', 'exists:agencies,id'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'patronymic' => ['nullable', 'string', 'max:255'],
            'passport' => ['required', 'string', 'max:255'],
            'insurer_name' => ['required', 'string', 'max:255'],
            'contract_name' => ['required', 'string', 'max:255'],
            'contract_series' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'agent_email' => ['required', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'is_draft' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'surname' => 'фамилия',
            'patronymic' => 'отчество',
            'passport' => 'паспорт',
            'insurer_name' => 'страховая компания',
            'contract_name' => 'тип договора',
            'contract_series' => 'серия договора',
            'contract_number' => 'номер договора',
            'client_email' => 'email клиента',
            'agent_email' => 'email агента',
            'amount' => 'сумма договора',
        ];
    }
}
