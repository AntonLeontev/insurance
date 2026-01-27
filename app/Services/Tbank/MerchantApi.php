<?php

namespace App\Services\Tbank;

use App\Services\Tbank\Exceptions\MerchantApiException;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MerchantApi
{
    public function __construct(
        public string $terminal,
        public string $password,
    ) {}

    public function client(): PendingRequest
    {
        return Http::asJson()
            ->retry(2, 500)
            ->acceptJson()
            ->baseUrl('https://securepay.tinkoff.ru/v2/')
            ->throw(function (Response $response) {
                throw new MerchantApiException($response);
            });
    }

    /**
     * Инициирует оплату
     *
     * @param  int  $amount  cумма в копейках
     */
    public function init(
        int $amount,
        string|int $orderId,
        string $email,
        ?string $description = null,
        ?Carbon $dueDate = null,
        ?string $successUrl = null,
        ?string $failUrl = null,
        ?string $notificationUrl = null,
    ): Response {
        $data = [
            'TerminalKey' => $this->terminal,
            'Amount' => $amount,
            'OrderId' => $orderId,
            'Description' => $description,
            'SuccessURL' => $successUrl,
            'RedirectDueDate' => $dueDate?->format('Y-m-d\TH:i:s\Z') ?? now()->addDay()->format('Y-m-d\TH:i:s\Z'),
            'DATA' => [
                // 'Phone' => $phone,
                'Email' => $email,
            ],
        ];

        if ($failUrl !== null) {
            $data['FailURL'] = $failUrl;
        }
        if ($notificationUrl !== null) {
            $data['NotificationURL'] = $notificationUrl;
        }

        $token = $this->makeToken($data);
        $data['Token'] = $token;

        $response = $this->client()->post('Init', $data);

        $this->checkResponse($response);

        return $response;
    }

    public function getState(string $paymentId): Response
    {
        $data = [
            'TerminalKey' => $this->terminal,
            'PaymentId' => $paymentId,
        ];

        $token = $this->makeToken($data);
        $data['Token'] = $token;

        $response = $this->client()->post('GetState', $data);

        $this->checkResponse($response);

        return $response;
    }

    private function makeToken(array $args): string
    {
        $token = '';
        $args['Password'] = $this->password;
        ksort($args);

        foreach ($args as $arg) {
            if (! is_array($arg)) {
                $token .= $arg;
            }
        }

        return hash('sha256', $token);
    }

    /**
     * Проверяет токен вебхука согласно документации Тинькофф
     *
     * @param  array  $data  Данные вебхука (все параметры кроме Token)
     * @param  string  $receivedToken  Токен, полученный в вебхуке
     */
    public function verifyWebhookToken(array $data, string $receivedToken): bool
    {
        // Исключаем Token и вложенные объекты (Data, Receipt)
        $filteredData = [];
        foreach ($data as $key => $value) {
            if ($key === 'Token') {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $filteredData[$key] = $value;
        }

        // Генерируем токен из отфильтрованных данных
        $calculatedToken = $this->makeToken($filteredData);

        return hash_equals($calculatedToken, $receivedToken);
    }

    /**
     * @throws MerchantApiException
     */
    private function checkResponse(Response $response): true
    {
        if (! $response->json('Success')) {
            throw new MerchantApiException($response);
        }

        return true;
    }
}
