<?php

namespace App\Services\GoogleSheets;

use App\Models\Payment;
use App\Models\Receipt;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class AppendPaymentRowToGoogleSheet
{
    public function append(Payment $payment, Receipt $receipt): void
    {
        if (! config('google_sheets.enabled')) {
            return;
        }

        if ($receipt->agency_id !== config('google_sheets.agency_id')) {
            return;
        }

        $spreadsheetId = config('google_sheets.spreadsheet_id');
        $range = config('google_sheets.append_range');
        $credentialsPath = storage_path('app/private/'.config('google_sheets.credentials_file_name'));

        if (! $spreadsheetId || ! $range || ! $credentialsPath) {
            Log::warning('Google Sheets: не заданы spreadsheet_id, append_range или credentials_path');

            return;
        }

        $resolvedPath = $this->resolveCredentialsPath($credentialsPath);

        if (! is_readable($resolvedPath)) {
            Log::warning('Google Sheets: файл ключа недоступен для чтения', [
                'path' => $resolvedPath,
            ]);

            return;
        }

        $receipt->loadMissing('user');

        $row = [
            (string) $payment->id,
            (string) ($receipt->user?->name ?? ''),
            $this->clientFullName($receipt),
            (string) ($receipt->insurer_name ?? ''),
            (string) ($receipt->contract_name ?? ''),
            (string) ($receipt->contract_series ?? ''),
            (string) ($receipt->contract_number ?? ''),
            $receipt->amount,
            (string) ($payment->payment_id ?? ''),
            (string) ($payment->redirect_url ?? ''),
        ];

        $client = new Client;
        $client->setAuthConfig($resolvedPath);
        $client->addScope(Sheets::SPREADSHEETS);

        $sheets = new Sheets($client);
        $body = new ValueRange(['values' => [$row]]);

        $sheets->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    private function resolveCredentialsPath(string $path): string
    {
        if ($path !== '' && $path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return base_path($path);
    }

    private function clientFullName(Receipt $receipt): string
    {
        $parts = array_filter(
            [$receipt->surname, $receipt->name, $receipt->patronymic],
            static fn ($v) => $v !== null && $v !== ''
        );

        return trim(implode(' ', $parts));
    }
}
