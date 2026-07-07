<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use App\Rules\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AgencyUpdateDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $this->route('agency')->id)->first();
        if (empty($agencyUser)) {
            return false;
        }

        return $agencyUser->role === Role::ADMIN;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'numeric', new Digits(10, 12)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название компании',
            'inn' => 'ИНН',
        ];
    }
}
