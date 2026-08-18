<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\AgencyUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReceiptExportRequest extends ReceiptIndexRequest
{
    public function authorize(): bool
    {
        $agencyUser = AgencyUser::query()
            ->where('user_id', Auth::id())
            ->where('agency_id', $this->input('agency_id'))
            ->first();

        return $agencyUser !== null && $agencyUser->role === Role::ADMIN;
    }

    protected function failedAuthorization(): void
    {
        abort(Response::HTTP_FORBIDDEN, 'Доступ запрещен');
    }
}
