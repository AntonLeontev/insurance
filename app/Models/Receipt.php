<?php

namespace App\Models;

use App\Casts\AmountCast;
use App\Enums\PaymentType;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Receipt extends Model
{
    protected $fillable = [
        'agency_id',
        'user_id',
        'name',
        'surname',
        'patronymic',
        'passport',
        'insurer_name',
        'contract_name',
        'contract_series',
        'contract_number',
        'client_email',
        'agent_email',
        'amount',
        'is_draft',
        'payment_type',
        'status',
        'fiscal_receipt_number',
        'shift_number',
        'receipt_datetime',
        'fn_number',
        'ecr_registration_number',
        'fiscal_document_number',
        'fiscal_document_attribute',
        'ofd_receipt_url',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'payment_type' => PaymentType::class,
        'amount' => AmountCast::class,
    ];

    public function scopeAvaliableForUser(Builder $query)
    {
        if (Auth::user()->role === Role::CASHIER) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('agency_id', Auth::user()->agency_id);
        }
    }
}
