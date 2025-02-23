<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractDestroyRequest;
use App\Http\Requests\ContractStoreRequest;
use App\Http\Requests\ContractUpdateRequest;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function store(ContractStoreRequest $request): JsonResponse
    {
        $contract = Contract::create($request->validated());

        return response()->json($contract);
    }

    public function destroy(Contract $contract, ContractDestroyRequest $request): void
    {
        $contract->delete();
    }

    public function update(Contract $contract, ContractUpdateRequest $request)
    {
        $contract->update($request->validated());

        return response()->json($contract);
    }
}
