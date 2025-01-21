<?php

namespace App\Services\Atol\Exceptions;

use Illuminate\Http\Client\Response;

class AtolException extends \Exception
{
    public function __construct(Response $response)
    {
        $this->message = 'Ошибка Атол: '.$response->json('error.text');
    }
}
