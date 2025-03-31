<?php

namespace App\Services\Atol;

use App\DTO\ReceiptRequestDTO;
use App\Services\Atol\Enums\ApiVersion;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AtolApi
{
    public function getToken(string $login, string $password, ApiVersion $version): Response
    {
        return Http::atol($version)
            ->post('getToken', [
                'login' => $login,
                'pass' => $password,
            ]);
    }

    public function sell(string $token, string $groupCode, ReceiptRequestDTO $data): Response
    {
        return Http::atol($data->version)
            ->withHeader('Token', $token)
            ->post($groupCode.'/sell', $data->toArray());
    }

    public function sellRefund(string $token, string $groupCode, ReceiptRequestDTO $data): Response
    {
        return Http::atol($data->version)
            ->withHeader('Token', $token)
            ->post($groupCode.'/sell_refund', $data->toArray());
    }

    public function report(string $token, string $groupCode, string $uuid, ApiVersion $version): Response
    {
        return Http::atol($version)
            ->withHeader('Token', $token)
            ->get($groupCode.'/report/'.$uuid);
    }
}
