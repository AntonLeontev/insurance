<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AgencyUpdateTbankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $this->route('agency')->id)->first();
        if (empty($agencyUser)) {
            return false;
        }

        return $agencyUser->role === Role::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'terminal' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'terminal' => 'Терминал',
            'password' => 'Пароль',
        ];
    }
}
