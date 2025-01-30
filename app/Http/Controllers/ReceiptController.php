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
            ->when($request->has('filters'), function ($query) use ($request) {
                foreach ($request->get('filters') as $filter) {
                    $query->where($filter['column'], $filter['value']);
                }
            })
            ->when($request->has('sort'), function ($query) use ($request) {
                foreach ($request->get('sort') as $sort) {
                    $query->orderBy($sort['key'], $sort['order']);
                }
            })
            ->when(! $request->has('sort'), function ($query) {
                $query->orderBy('id', 'desc');
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->get('search').'%')
                        ->orWhere('surname', 'like', '%'.$request->get('search').'%')
                        ->orWhere('patronymic', 'like', '%'.$request->get('search').'%')
                        ->orWhere('contract_number', 'like', '%'.$request->get('search').'%')
                        ->orWhere('contract_series', 'like', '%'.$request->get('search').'%');
                });
            })
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
