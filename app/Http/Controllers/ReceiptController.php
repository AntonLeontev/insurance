<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptStoreRequest;
use App\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $receipts = Receipt::query()
            ->when($request->has('filters'), function ($query) use ($request) {
                foreach ($request->get('filters') as $key => $filter) {
                    $query->where($filter['column'], $filter['sign'] ?? '=', $filter['value']);
                }
            })
            ->paginate();

        return response()->json($receipts);
    }

    public function store(ReceiptStoreRequest $request)
    {
        Receipt::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);
    }
}
