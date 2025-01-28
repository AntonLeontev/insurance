<?php

namespace App\Http\Requests;

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

        return $insurer->agency_id === Auth::user()->agency_id;
    }
}
