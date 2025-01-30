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

    public function scopeSort(Builder $query)
    {
        $query->when(request()->has('sort'), function ($query) {
            foreach (request()->get('sort') as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        })
            ->when(! request()->has('sort'), function ($query) {
                $query->orderBy('id', 'desc');
            });
    }

    public function scopeSearch(Builder $query)
    {
        $query->when(request()->has('search'), function ($query) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.request()->get('search').'%')
                    ->orWhere('surname', 'like', '%'.request()->get('search').'%')
                    ->orWhere('patronymic', 'like', '%'.request()->get('search').'%')
                    ->orWhere('contract_number', 'like', '%'.request()->get('search').'%')
                    ->orWhere('contract_series', 'like', '%'.request()->get('search').'%');
            });
        });
    }

    public function scopeFilters(Builder $query)
    {
        $query->when(request()->has('filters'), function ($query) {
            foreach (request()->get('filters') as $filter) {
                $query->where($filter['column'], $filter['value']);
            }
        });
    }
}
