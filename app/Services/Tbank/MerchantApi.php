<?php

namespace App\Services\Tbank;

use App\Services\Tbank\Exceptions\MerchantApiException;
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
        int $orderId,
        string $phone,
        string $email,
        ?string $description = null,
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
            'DATA' => [
                'Phone' => $phone,
                'Email' => $email,
            ],
            'Receipt' => [
                'Email' => $email,
                'EmailCompany' => 'contact@cookforia.ru',
                'FfdVersion' => '1.2',
                'Taxation' => 'usn_income_outcome',
                'Items' => [
                    [
                        'Name' => $description,
                        'Price' => $amount,
                        'Quantity' => 1.0,
                        'Amount' => $amount,
                        'PaymentMethod' => 'full_prepayment',
                        'PaymentObject' => 'service',
                        'MeasurementUnit' => '-',
                        'Tax' => 'none',
                    ],
                ],
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
