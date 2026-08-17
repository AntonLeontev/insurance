<?php

namespace App\Models;

use App\Casts\AmountCast;
use App\Enums\FilterOperator;
use App\Enums\PaymentType;
use App\Enums\ReceiptStatus;
use App\Enums\ReceiptType;
use App\Enums\Role;
use App\Enums\VatAmount;
use App\Http\Requests\ReceiptSubmitRequest;
use App\Services\FiscalCredentialResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Receipt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'external_id',
        'agency_id',
        'fiscal_credential_id',
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
        'parent_id',
        'is_checked',
        'checked_by_user_id',
        'checked_at',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
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

    public function scopeFilters(Builder $query): void
    {
        $query->when(request()->has('filters'), function (Builder $query): void {
            foreach (request()->get('filters') as $filter) {
                $query->where(
                    $filter['column'],
                    $filter['operator'] ?? FilterOperator::EQ->value,
                    $this->normalizedFilterValue($filter),
                );
            }
        });
    }

    private function normalizedFilterValue(array $filter): mixed
    {
        $value = $filter['value'];
        $column = $filter['column'] ?? null;
        $operator = $filter['operator'] ?? FilterOperator::EQ->value;

        if ($column !== 'submited_at' || ! is_string($value)) {
            return $value;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $date = Carbon::parse($value);

        return match ($operator) {
            FilterOperator::LTE->value, FilterOperator::LT->value => $date->endOfDay()->toDateTimeString(),
            default => $date->startOfDay()->toDateTimeString(),
        };
    }

    public static function fromSubmitRequest(ReceiptSubmitRequest $request): static
    {
        $resolver = app(FiscalCredentialResolver::class);
        $insurer = Insurer::find($request->validated('insurer_id'));
        $credential = $resolver->resolveForInsurer($insurer, $request->validated('agency_id'));

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
        $receipt->agent_email = $credential->email;
        $receipt->amount = $request->validated('amount');
        $receipt->payment_type = $request->validated('payment_type');
        $receipt->save();

        return $receipt;
    }

    public function fiscalCredential(): BelongsTo
    {
        return $this->belongsTo(FiscalCredential::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latest();
    }
}
