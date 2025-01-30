<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReceiptDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (Auth::user()->role === Role::CASHIER) {
            return Auth::id() === $this->route('receipt')->user_id;
        } else {
            return Auth::user()->agency_id === $this->route('receipt')->agency_id;
        }
    }
}
