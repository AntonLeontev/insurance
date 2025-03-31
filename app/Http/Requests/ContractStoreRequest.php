<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Enums\VatAmount;
use App\Models\AgencyUser;
use App\Models\Insurer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContractStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $insurerAgencyId = Insurer::find($this->get('insurer_id'))->agency_id;
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $insurerAgencyId)->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insurer_id' => ['required', 'int'],
            'name' => ['required', 'string', 'max:255'],
            'vat' => ['required', Rule::enum(VatAmount::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'vat' => 'НДС',
        ];
    }
}
