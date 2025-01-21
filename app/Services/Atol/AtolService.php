<?php

namespace App\Services\Atol;

class AtolService
{
    public function __construct(public AtolApi $api) {}

    public function getToken(?string $login = null, ?string $password = null): string
    {
        $response = $this->api->getToken($login, $password);

        return $response->json('token');
    }
}
