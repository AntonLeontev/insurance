<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InsurerDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $insurerAgencyId = $this->route('insurer')->agency_id;
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $insurerAgencyId)->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }
}
