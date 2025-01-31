<?php

namespace App\Services\Atol;

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

        $clientName = "{$receipt->surname} {$receipt->name}";
        $clientName .= $receipt->patronymic
            ? " {$receipt->patronymic} {$receipt->passport}"
            : " {$receipt->passport}";

        $response = $this->api->sell($token, $agency->group_code, [
            'external_id' => $receipt->id,
            'receipt' => [
                'client' => [
                    'email' => $receipt->client_email,
                    'name' => $clientName,
                ],
                'company' => [
                    'email' => $receipt->agent_email,
                    'sno' => $agency->sno->value,
                    'inn' => $agency->inn,
                    'payment_address' => $agency->payment_address,
                ],
                'items' => [
                    [
                        'name' => "Договор страхования {$receipt->contract_series} {$receipt->contract_number}, вид страхования – {$receipt->contract_name}",
                        'price' => $receipt->amount,
                        'quantity' => 1,
                        'sum' => $receipt->amount,
                        'measurement_unit' => 'Полис',
                        'payment_method' => 'full_payment',
                        'payment_object' => 'service',
                        'agent_info' => ['type' => 'commission_agent'],
                        'supplier_info' => [
                            'name' => $receipt->insurer_name,
                            'inn' => $receipt->insurer_inn,
                        ],
                        'vat' => ['type' => 'none'],
                    ],
                ],
                'payments' => [[
                    'type' => $receipt->payment_type->atolType(),
                    'sum' => $receipt->amount,
                ]],
                'total' => $receipt->amount,
            ],
            'service' => [
                'callback_url' => 'http://45.146.165.254:8080/webhooks/atol',
            ],
            'timestamp' => $receipt->submited_at->format('d.m.Y H:i:s'),
        ]);

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
