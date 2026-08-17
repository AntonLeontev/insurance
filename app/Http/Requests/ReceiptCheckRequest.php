<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReceiptCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        $receipt = $this->route('receipt');
        $agencyUser = AgencyUser::query()
            ->where('user_id', Auth::id())
            ->where('agency_id', $receipt->agency_id)
            ->first();

        if ($agencyUser === null) {
            return false;
        }

        return in_array($agencyUser->role, [Role::ADMIN, Role::ACCOUNTANT], true);
    }

    protected function failedAuthorization(): void
    {
        abort(Response::HTTP_FORBIDDEN, 'Доступ запрещен');
    }
}
