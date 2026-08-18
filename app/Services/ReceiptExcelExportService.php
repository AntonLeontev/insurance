<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Builder;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptExcelExportService
{
    public function download(Builder|iterable $receipts): BinaryFileResponse|StreamedResponse
    {
        $filename = 'cheki-'.now()->format('Y-m-d').'.xlsx';

        return (new FastExcel($this->rows($receipts)))->download($filename);
    }

    private function rows(Builder|iterable $receipts): iterable
    {
        $source = $receipts instanceof Builder
            ? $receipts->lazy()
            : $receipts;

        foreach ($source as $receipt) {
            yield $this->mapRow($receipt);
        }
    }

    private function mapRow(Receipt $receipt): array
    {
        return [
            'ФИО' => $this->fullName($receipt),
            'Договор' => trim("{$receipt->contract_series} {$receipt->contract_number}"),
            'Страховая' => $receipt->insurer_name,
            'Тип договора' => $receipt->contract_name,
            'Фискальные реквизиты' => $receipt->fiscalCredential?->name,
            'Тип чека' => $receipt->receipt_type?->label() ?? '',
            'Стоимость' => $receipt->amount,
            'Статус' => 'Успешно',
            'Эквайринг' => $this->acquiringLabel($receipt),
            'Кассир' => $receipt->user?->name,
            'Email кассира' => $receipt->user?->email,
            'Сверен' => $receipt->is_checked ? 'Да' : 'Нет',
            'ФН' => $receipt->fn_number,
            'ФПД' => $receipt->fiscal_document_attribute,
            'ФНД' => $receipt->fiscal_document_number,
        ];
    }

    private function fullName(Receipt $receipt): string
    {
        return collect([$receipt->surname, $receipt->name, $receipt->patronymic])
            ->filter()
            ->implode(' ');
    }

    private function acquiringLabel(Receipt $receipt): string
    {
        $payment = $receipt->payments->first();

        if ($payment === null) {
            return '';
        }

        if ($payment->status === 'REFUNDED') {
            return 'Возврат';
        }

        if ($payment->paid_at !== null) {
            return 'Оплачен';
        }

        return (string) $payment->status;
    }
}
