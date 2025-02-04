<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsurerDestroyRequest;
use App\Http\Requests\InsurerStoreRequest;
use App\Http\Requests\InsurerUpdateRequest;
use App\Models\Insurer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsurerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $insurers = Insurer::where('agency_id', $request->get('agency_id'))
            ->with(['contracts' => fn ($q) => $q->select(['id', 'name', 'insurer_id', 'vat'])])
            ->get(['id', 'name', 'inn']);

        return response()->json($insurers);
    }

    public function store(InsurerStoreRequest $request): JsonResponse
    {
        $insurer = Insurer::create([
            ...$request->validated(),
            'agency_id' => $request->get('agency_id'),
        ]);

        return response()->json($insurer);
    }

    public function update(InsurerUpdateRequest $request): JsonResponse
    {
        $insurer = Insurer::find($request->get('id'));
        $insurer->update($request->validated());

        return response()->json($insurer);
    }

    public function destroy(Insurer $insurer, InsurerDestroyRequest $request): void
    {
        $insurer->delete();
    }
}
