<?php

namespace App\Services\Atol;

use App\DTO\ReceiptRequestDTO;
use App\Models\FiscalCredential;
use App\Models\Receipt;
use App\Services\Atol\Enums\ApiVersion;
use Illuminate\Http\Client\Response;

class AtolService
{
    public function __construct(public AtolApi $api) {}

    public function getToken(string $login, string $password, ApiVersion $version): string
    {
        $response = $this->api->getToken($login, $password, $version);

        return $response->json('token');
    }

    public function sell(Receipt $receipt, FiscalCredential $credential)
    {
        $token = $this->loadToken($credential);

        $dto = ReceiptRequestDTO::fromReceipt($receipt, $credential, $credential->ffd->ApiVersion());

        $response = $this->api->sell($token, $credential->group_code, $dto);

        return $response->object();
    }

    public function sellRefund(Receipt $receipt, FiscalCredential $credential)
    {
        $token = $this->loadToken($credential);

        $dto = ReceiptRequestDTO::fromReceipt($receipt, $credential, $credential->ffd->ApiVersion());

        $response = $this->api->sellRefund($token, $credential->group_code, $dto);

        return $response->object();
    }

    public function report(Receipt $receipt, FiscalCredential $credential): Response
    {
        $token = $this->loadToken($credential);

        return $this->api->report($token, $credential->group_code, $receipt->external_id, $credential->ffd->ApiVersion());
    }

    private function loadToken(FiscalCredential $credential): string
    {
        if ($credential->atol_token_expires?->isFuture()) {
            return $credential->atol_token;
        }

        $token = $this->getToken($credential->atol_login, $credential->atol_password, $credential->ffd->ApiVersion());

        $credential->update([
            'atol_token' => $token,
            'atol_token_expires' => now()->addHours(24),
        ]);

        return $token;
    }
}
