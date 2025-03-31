<?php

namespace App\Http\Requests;

use App\Enums\PaymentType;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReceiptSubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return AgencyUser::where('user_id', Auth::id())->where('agency_id', $this->get('agency_id'))->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'exists:receipts,id'],
            'agency_id' => ['required', 'exists:agencies,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'patronymic' => ['nullable', 'string', 'max:255'],
            'passport' => ['required', 'string', 'max:255'],
            'insurer_id' => ['required', 'exists:insurers,id'],
            'contract_id' => ['required', 'exists:contracts,id'],
            'contract_series' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'agent_email' => ['required', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'is_draft' => ['required', 'boolean'],
            'payment_type' => ['required', 'string', Rule::enum(PaymentType::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'surname' => 'фамилия',
            'patronymic' => 'отчество',
            'passport' => 'паспорт',
            'insurer_id' => 'страховая компания',
            'contract_id' => 'тип договора',
            'contract_series' => 'серия договора',
            'contract_number' => 'номер договора',
            'client_email' => 'email клиента',
            'agent_email' => 'email агента',
            'amount' => 'сумма договора',
        ];
    }
}
