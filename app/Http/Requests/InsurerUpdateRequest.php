<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Insurer;
use App\Rules\Digits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InsurerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->role === Role::ADMIN
            && Auth::user()->agency_id === Insurer::find($this->input('id'))->agency_id;
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
