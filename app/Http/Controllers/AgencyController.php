<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyUpdateAtolRequest;
use App\Http\Requests\AgencyUpdateDetailsRequest;
use App\Services\Atol\AtolService;

class AgencyController extends Controller
{
    public function updateDetails(AgencyUpdateDetailsRequest $request)
    {
        $agency = auth()->user()->agency;
        $agency->update($request->validated());

        return response()->json($agency);
    }

    public function updateAtol(AgencyUpdateAtolRequest $request, AtolService $service)
    {
        $token = $service->getToken($request->validated('atol_login'), $request->validated('atol_password'));

        $data = [
            ...$request->validated(),
            'atol_token' => $token,
            'atol_token_expires' => now()->addHours(24),
        ];

        $agency = auth()->user()->agency;
        $agency->update($data);
    }
}
