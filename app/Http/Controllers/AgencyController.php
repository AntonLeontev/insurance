<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyUpdateAtolRequest;
use App\Http\Requests\AgencyUpdateDetailsRequest;

class AgencyController extends Controller
{
    public function updateDetails(AgencyUpdateDetailsRequest $request)
    {
        $agency = auth()->user()->agency;
        $agency->update($request->validated());

        return response()->json($agency);
    }

    public function updateAtol(AgencyUpdateAtolRequest $request)
    {
        $agency = auth()->user()->agency;
        $agency->update($request->validated());
    }
}
