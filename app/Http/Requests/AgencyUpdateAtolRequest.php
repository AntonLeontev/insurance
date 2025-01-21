<?php

namespace App\Http\Requests;

use App\Enums\Ffd;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AgencyUpdateAtolRequest extends FormRequest
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
            'atol_login' => ['required', 'string', 'max:255'],
            'atol_password' => ['required', 'string', 'max:255'],
            'ffd' => ['required', 'string', new Enum(Ffd::class)],
            'group_code' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'atol_login' => 'Логин Атол',
            'atol_password' => 'Пароль',
            'ffd' => 'ФФД',
            'group_code' => 'Код группы',
        ];
    }
}
