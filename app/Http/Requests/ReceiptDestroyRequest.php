<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReceiptDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $receipt = $this->route('receipt');
        $agencyId = $receipt->agency_id;
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $agencyId)->first();

        if ($agencyUser === null) {
            return false;
        }

        if ($agencyUser->role === Role::CASHIER) {
            return Auth::id() === $receipt->user_id;
        } else {
            return true;
        }
    }
}
