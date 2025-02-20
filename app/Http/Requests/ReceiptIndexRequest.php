<?php

namespace App\Http\Requests;

use App\Enums\FilterOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiptIndexRequest extends FormRequest
{
    public function rules(): array
    {
        $columns = [
            'id',
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

        return [
            'filters' => ['sometimes', 'array'],
            'filters.*.column' => ['required', Rule::in($columns)],
            'filters.*.operator' => ['sometimes', Rule::enum(FilterOperator::class)],
            'filters.*.value' => ['required'],
            'sort' => ['sometimes', 'array'],
            'sort.*.key' => ['required', Rule::in($columns)],
            'sort.*.order' => ['required', 'in:asc,desc'],
            'items_per_page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string'],
        ];
    }
}
