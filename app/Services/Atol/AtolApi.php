<?php

namespace App\Services\Atol;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AtolApi
{
    public function getToken(?string $login = null, ?string $password = null): Response
    {
        $login = $login ?? auth()->user()->agency?->atol_login;
        $password = $password ?? auth()->user()->agency?->atol_password;

        return Http::atol()
            ->post('getToken', [
                'login' => $login,
                'pass' => $password,
            ]);
    }

    public function sell(string $token, string $groupCode, array $data): Response
    {
        return Http::atol()
            ->withHeader('Token', $token)
            ->post($groupCode.'/sell', $data);
    }
}
