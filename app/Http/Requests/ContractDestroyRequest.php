<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use App\Models\Insurer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ContractDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $insurer = Insurer::find($this->route('contract')->insurer_id);
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $insurer->agency_id)->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }
}
