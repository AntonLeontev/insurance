<?php

namespace App\Services\Kkt;

use App\DTO\AtolExportReceipt;
use App\Enums\PaymentType;
use App\Enums\ReceiptType;
use App\Enums\VatAmount;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Throwable;

class AtolExportReader
{
    /**
     * @var list<string>
     */
    private const COLUMNS = [
        'Тип',
        'Дата обработки',
        'External_id',
        'UUID',
        'Email',
        'Сумма чека, зарегистрированная на кассе',
        'JSON',
        'РНМ кассы',
        'ФПД',
        'Номер смены',
        'ФД',
        'Номер ФН',
        'Результат обработки',
        'Групп-код',
    ];

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const RECEIPT_TYPES = [
        'Приход' => [ReceiptType::SELL->value, 'Sell'],
        'Возврат прихода' => [ReceiptType::SELL_REFUND->value, 'SellRefund'],
    ];

    /**
     * @return list<AtolExportReceipt>
     */
    public function read(string $path): array
    {
        $reader = new Reader;

        try {
            $reader->open($path);
        } catch (Throwable $exception) {
            Log::error('[AtolExportReader] Файл не открывается', [
                'file' => $path,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        try {
            return $this->readFirstSheet($reader, $path);
        } finally {
            $reader->close();
        }
    }

    /**
     * @return list<AtolExportReceipt>
     */
    private function readFirstSheet(Reader $reader, string $path): array
    {
        foreach ($reader->getSheetIterator() as $sheet) {
            return $this->readRows($sheet->getRowIterator(), $path);
        }

        Log::error('[AtolExportReader] Файл не открывается', [
            'file' => $path,
            'exception' => 'В книге нет листов',
        ]);

        throw new RuntimeException('В книге нет листов');
    }

    /**
     * @param  iterable<int, Row>  $rows
     * @return list<AtolExportReceipt>
     */
    private function readRows(iterable $rows, string $path): array
    {
        $headerIndex = null;
        $receipts = [];
        $rowNumber = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            $cells = $row->toArray();

            if ($headerIndex === null) {
                $headerIndex = $this->headerIndex($cells, $path);

                continue;
            }

            if ($this->rowIsEmpty($cells)) {
                continue;
            }

            $receipts[] = $this->readRow($rowNumber, $cells, $headerIndex);
        }

        return $receipts;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array<string, int>
     */
    private function headerIndex(array $cells, string $path): array
    {
        $index = [];

        foreach ($cells as $column => $cell) {
            $header = $this->stringCell($cell);

            if ($header !== '') {
                $index[$header] = $column;
            }
        }

        $missing = array_values(array_filter(
            self::COLUMNS,
            fn (string $column): bool => ! array_key_exists($column, $index),
        ));

        if ($missing !== []) {
            Log::error('[AtolExportReader] Файл не открывается', [
                'file' => $path,
                'exception' => 'Нет колонок: '.implode(', ', $missing),
            ]);

            throw new RuntimeException('В файле нет колонок: '.implode(', ', $missing));
        }

        return $index;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $headerIndex
     */
    private function readRow(int $rowNumber, array $cells, array $headerIndex): AtolExportReceipt
    {
        $value = fn (string $column): mixed => $cells[$headerIndex[$column]] ?? null;
        $externalId = $this->nullableString($value('External_id'));
        $atolUuid = $this->nullableString($value('UUID'));
        $groupCode = $this->nullableString($value('Групп-код'));

        if ($this->stringCell($value('Результат обработки')) !== 'Зарегистрирован') {
            return new AtolExportReceipt(
                rowNumber: $rowNumber,
                externalId: $externalId,
                atolUuid: $atolUuid,
                groupCode: $groupCode,
                skippedByStatus: true,
                error: null,
            );
        }

        if ($externalId === null) {
            return $this->invalid($rowNumber, null, $atolUuid, $groupCode, 'Нет External_id', false);
        }

        $payload = $this->decodePayload($value('JSON'));

        if ($payload === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'JSON не разобран', true);
        }

        $typeLabel = $this->stringCell($value('Тип'));
        $receiptType = self::RECEIPT_TYPES[$typeLabel] ?? null;

        if ($receiptType === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'Тип чека не узнан', true);
        }

        $documentType = $payload['document_type'] ?? null;

        if ($documentType !== $receiptType[1]) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'Тип чека не совпадает с document_type', true);
        }

        $paymentType = $this->paymentType($payload);

        if (is_array($paymentType)) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, $paymentType['error'], true);
        }

        $item = $this->item($payload);

        if ($item === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'В чеке не одна позиция', false);
        }

        $vat = $item['vat']['type'] ?? null;

        if (! is_string($vat) || VatAmount::tryFrom($vat) === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'НДС не разобран', false);
        }

        $amount = $this->amountKopecks($value('Сумма чека, зарегистрированная на кассе'));

        if ($amount === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'Сумма чека не разобрана', false);
        }

        $submittedAt = $this->dateTime($value('Дата обработки'));

        if ($submittedAt === null) {
            return $this->invalid($rowNumber, $externalId, $atolUuid, $groupCode, 'Дата обработки не разобрана', false);
        }

        $client = $this->clientName((string) ($payload['receipt']['client']['name'] ?? ''));
        $contract = $this->contract((string) ($item['name'] ?? ''));
        $supplier = is_array($item['supplier_info'] ?? null) ? $item['supplier_info'] : [];

        return new AtolExportReceipt(
            rowNumber: $rowNumber,
            externalId: $externalId,
            atolUuid: $atolUuid,
            groupCode: $groupCode,
            skippedByStatus: false,
            error: null,
            receiptType: $receiptType[0],
            surname: $client['surname'],
            name: $client['name'],
            patronymic: $client['patronymic'],
            passport: $client['passport'],
            insurerName: $this->nullableString($supplier['name'] ?? null),
            insurerInn: $this->nullableString($supplier['inn'] ?? null),
            contractName: $contract['name'],
            contractSeries: $contract['series'],
            contractNumber: $contract['number'],
            vat: $vat,
            clientEmail: $this->nullableString($value('Email')),
            agentEmail: $this->nullableString($payload['receipt']['company']['email'] ?? null),
            amount: $amount,
            paymentType: $paymentType,
            shiftNumber: $this->nullableString($value('Номер смены')),
            receiptDatetime: $submittedAt,
            fnNumber: $this->nullableString($value('Номер ФН')),
            ecrRegistrationNumber: $this->nullableString($value('РНМ кассы')),
            fiscalDocumentNumber: $this->nullableString($value('ФД')),
            fiscalDocumentAttribute: $this->nullableString($value('ФПД')),
            submittedAt: $submittedAt,
        );
    }

    private function invalid(
        int $rowNumber,
        ?string $externalId,
        ?string $atolUuid,
        ?string $groupCode,
        string $message,
        bool $log,
    ): AtolExportReceipt {
        if ($log) {
            Log::warning('[AtolExportReader] Строка не разобрана', [
                'row' => $rowNumber,
                'external_id' => $externalId,
                'message' => $message,
            ]);
        }

        return new AtolExportReceipt(
            rowNumber: $rowNumber,
            externalId: $externalId,
            atolUuid: $atolUuid,
            groupCode: $groupCode,
            skippedByStatus: false,
            error: $message,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(mixed $cell): ?array
    {
        if (is_array($cell)) {
            return $cell;
        }

        $json = str_replace('\\\\"', '\\"', $this->stringCell($cell));
        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return string|array{error: string}
     */
    private function paymentType(array $payload): string|array
    {
        $payments = $payload['receipt']['payments'] ?? null;

        if (! is_array($payments) || count($payments) !== 1 || ! is_array($payments[0] ?? null)) {
            return ['error' => 'В чеке не одна оплата'];
        }

        $type = $payments[0]['type'] ?? null;

        if ($type === 0 || $type === '0') {
            return PaymentType::CASH->value;
        }

        if ($type === 1 || $type === '1') {
            return PaymentType::CASHLESS->value;
        }

        return ['error' => 'Тип оплаты не узнан'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function item(array $payload): ?array
    {
        $items = $payload['receipt']['items'] ?? null;

        if (! is_array($items) || count($items) !== 1 || ! is_array($items[0] ?? null)) {
            return null;
        }

        return $items[0];
    }

    /**
     * @return array{surname: ?string, name: ?string, patronymic: ?string, passport: ?string}
     */
    private function clientName(string $clientName): array
    {
        $passport = null;
        $rest = trim($clientName);

        if (preg_match('/^(.*?)(\d{4}[\s-]?\d{6})\s*$/u', $rest, $matches) === 1) {
            $passport = $matches[2];
            $rest = trim($matches[1]);
        }

        $words = preg_split('/\s+/u', $rest, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_values(array_filter(
            $words === false ? [] : $words,
            fn (string $word): bool => preg_match('/^\.+$/u', $word) !== 1,
        ));

        if ($passport === null) {
            return [
                'surname' => $this->nullableString(implode(' ', $words)),
                'name' => null,
                'patronymic' => null,
                'passport' => null,
            ];
        }

        return [
            'surname' => $words[0] ?? null,
            'name' => $words[1] ?? null,
            'patronymic' => $this->nullableString(implode(' ', array_slice($words, 2))),
            'passport' => $passport,
        ];
    }

    /**
     * @return array{series: ?string, number: ?string, name: ?string}
     */
    private function contract(string $itemName): array
    {
        $name = null;

        if (preg_match('/вид страхования\s*[–—-]\s*(.+)$/u', $itemName, $matches) === 1) {
            $name = $this->nullableString($matches[1]);
        }

        $series = null;
        $number = null;

        if (preg_match('/Договор страхования\s+(.+?),/u', $itemName, $matches) === 1) {
            $fragment = trim($matches[1]);
            $words = preg_split('/\s+/u', $fragment, -1, PREG_SPLIT_NO_EMPTY);
            $words = $words === false ? [] : $words;
            $number = $fragment;

            if (count($words) === 2) {
                $series = $words[0];
                $number = $words[1];
            }
        }

        return [
            'series' => $series,
            'number' => $number,
            'name' => $name,
        ];
    }

    private function amountKopecks(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }

        $amount = str_replace(',', '.', $this->stringCell($value));

        if ($amount === '' || ! is_numeric($amount)) {
            return null;
        }

        return (int) round((float) $amount * 100);
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        $date = $this->stringCell($value);

        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($this->stringCell($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = $this->stringCell($value);

        return $string === '' ? null : $string;
    }

    private function stringCell(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        if ($value === null || is_bool($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
