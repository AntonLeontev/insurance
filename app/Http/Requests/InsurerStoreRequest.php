<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use App\Rules\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InsurerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $this->input('agency_id'))->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', new Digits(10, 12)],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Название',
            'inn' => 'ИНН',
        ];
    }
}
