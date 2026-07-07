<?php

namespace App\Http\Requests;

use App\Enums\Ffd;
use App\Enums\Role;
use App\Enums\Sno;
use App\Models\AgencyUser;
use App\Rules\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class FiscalCredentialStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agencyUser = AgencyUser::where('user_id', Auth::id())
            ->where('agency_id', $this->get('agency_id'))
            ->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }

    public function rules(): array
    {
        return [
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'inn' => ['required', 'numeric', new Digits(10, 12)],
            'email' => ['required', 'email', 'max:64'],
            'sno' => ['required', 'string', new Enum(Sno::class)],
            'payment_address' => ['required', 'string', 'max:255'],
            'receipt_email' => ['nullable', 'email', 'max:255'],
            'atol_login' => ['required', 'string', 'max:255'],
            'atol_password' => ['required', 'string', 'max:255'],
            'ffd' => ['required', 'string', new Enum(Ffd::class)],
            'group_code' => ['required', 'string', 'max:255'],
            'terminal' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'required_with:terminal', 'string', 'max:255'],
            'insurer_ids' => ['sometimes', 'array'],
            'insurer_ids.*' => ['integer', 'exists:insurers,id'],
        ];
    }
}
