<?php

namespace App\Models;

use App\Casts\AmountCast;
use App\Enums\PaymentType;
use App\Enums\ReceiptStatus;
use App\Enums\ReceiptType;
use App\Enums\Role;
use App\Enums\VatAmount;
use App\Http\Requests\ReceiptSubmitRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Receipt extends Model
{
    use HasUuids;

    protected $fillable = [
        'external_id',
        'agency_id',
        'user_id',
        'receipt_type',
        'name',
        'surname',
        'patronymic',
        'passport',
        'insurer_id',
        'insurer_name',
        'insurer_inn',
        'contract_id',
        'contract_name',
        'vat',
        'contract_series',
        'contract_number',
        'client_email',
        'agent_email',
        'amount',
        'is_draft',
        'payment_type',
        'status',
        'error_text',
        'fiscal_receipt_number',
        'shift_number',
        'receipt_datetime',
        'fn_number',
        'ecr_registration_number',
        'fiscal_document_number',
        'fiscal_document_attribute',
        'ofd_receipt_url',
        'submited_at',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'receipt_type' => ReceiptType::class,
        'payment_type' => PaymentType::class,
        'amount' => AmountCast::class,
        'submited_at' => 'datetime',
        'status' => ReceiptStatus::class,
        'vat' => VatAmount::class,
    ];

    public function scopeAvaliableForUser(Builder $query, int $agencyId)
    {
        $role = AgencyUser::where('user_id', Auth::id())->where('agency_id', $agencyId)->firstOrFail()->role;

        if ($role === Role::CASHIER) {
            $query->where('user_id', Auth::id())
                ->where('agency_id', $agencyId);
        } else {
            $query->where('agency_id', $agencyId);
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
                $query->where($filter['column'], $filter['operator'] ?? '=', $filter['value']);
            }
        });
    }

    public static function fromSubmitRequest(ReceiptSubmitRequest $request): static
    {
        $agency = Agency::find($request->validated('agency_id'));

        $receipt = new static;

        $receipt->name = $request->validated('name');
        $receipt->surname = $request->validated('surname');
        $receipt->patronymic = $request->validated('patronymic');
        $receipt->agency_id = $request->validated('agency_id');
        $receipt->user_id = Auth::id();
        $receipt->passport = $request->validated('passport');
        $receipt->insurer_id = $request->validated('insurer_id');
        $receipt->contract_id = $request->validated('contract_id');
        $receipt->contract_series = $request->validated('contract_series');
        $receipt->contract_number = $request->validated('contract_number');
        $receipt->client_email = $request->validated('client_email');
        $receipt->agent_email = $agency->email;
        $receipt->amount = $request->validated('amount');
        $receipt->payment_type = $request->validated('payment_type');
        $receipt->save();

        return $receipt;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
