<?php

namespace App\Services\Atol;

use App\DTO\ReceiptRequestDTO;
use App\Models\Agency;
use App\Models\Receipt;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;

class AtolService
{
    public function __construct(public AtolApi $api) {}

    public function getToken(?string $login = null, ?string $password = null): string
    {
        $response = $this->api->getToken($login, $password);

        return $response->json('token');
    }

    public function sell(Receipt $receipt)
    {
        $agency = Auth::user()->agency;

        $token = $this->loadToken($agency);

        $dto = ReceiptRequestDTO::fromReceipt($receipt, $agency, $agency->ffd->ApiVersion());

        $response = $this->api->sell($token, $agency->group_code, $dto->toArray());

        return $response->object();
    }

    public function sellRefund(Receipt $receipt)
    {
        $agency = Auth::user()->agency;

        $token = $this->loadToken($agency);

        $dto = ReceiptRequestDTO::fromReceipt($receipt, $agency, $agency->ffd->ApiVersion());

        $response = $this->api->sellRefund($token, $agency->group_code, $dto->toArray());

        return $response->object();
    }

    public function report(Receipt $receipt): Response
    {
        $agency = Auth::user()->agency;

        $token = $this->loadToken($agency);

        return $this->api->report($token, $agency->group_code, $receipt->external_id);
    }

    private function loadToken(Agency $agency): string
    {
        if ($agency->atol_token_expires->isFuture()) {
            $token = $agency->atol_token;
        } else {
            $token = $this->getToken();

            $agency->update([
                'atol_token' => $token,
                'atol_token_expires' => now()->addHours(24),
            ]);
        }

        return $token;
    }
}
