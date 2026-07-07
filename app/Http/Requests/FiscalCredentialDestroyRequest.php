<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FiscalCredentialDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->route('fiscalCredential');

        $agencyUser = AgencyUser::where('user_id', Auth::id())
            ->where('agency_id', $credential->agency_id)
            ->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }

    public function rules(): array
    {
        return [];
    }
}
