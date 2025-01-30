<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ReceiptDestroyRequest;
use App\Http\Requests\ReceiptStoreRequest;
use App\Http\Requests\ReceiptUpdateRequest;
use App\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): JsonResponse
    {
        if (Auth::user()->role === Role::CASHIER) {
            abort_if($receipt->user_id !== Auth::id(), Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        } else {
            abort_if($receipt->agency_id !== Auth::user()->agency_id, Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        }

        return response()->json($receipt);
    }

    public function index(Request $request): JsonResponse
    {
        $receipts = Receipt::query()
            ->avaliableForUser()
            ->filters()
            ->sort()
            ->search()
            ->paginate($request->get('items_per_page'));

        return response()->json($receipts);
    }

    public function store(ReceiptStoreRequest $request)
    {
        Receipt::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);
    }

    public function update(Receipt $receipt, ReceiptUpdateRequest $request)
    {
        abort_unless($receipt->is_draft, Response::HTTP_BAD_REQUEST, 'Нельзя обновить пробитый чек');

        $receipt->update($request->validated());
    }

    public function destroy(Receipt $receipt, ReceiptDestroyRequest $request)
    {
        abort_unless($receipt->is_draft, Response::HTTP_BAD_REQUEST, 'Нельзя удалить пробитый чек');

        $receipt->delete();
    }
}
