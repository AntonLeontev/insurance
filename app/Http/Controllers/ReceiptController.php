<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ReceiptDestroyRequest;
use App\Http\Requests\ReceiptStoreRequest;
use App\Http\Requests\ReceiptSubmitRequest;
use App\Http\Requests\ReceiptUpdateRequest;
use App\Models\Contract;
use App\Models\Insurer;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
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
        $insurer = Insurer::find($request->get('insurer_id'));
        $contract = Contract::find($request->get('contract_id'));

        abort_if($contract->insurer_id !== $insurer->id, Response::HTTP_BAD_REQUEST, 'Контракт не принадлежит выбранному страховщику');

        Receipt::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
            'insurer_name' => $insurer->name,
            'insurer_inn' => $insurer->inn,
            'contract_name' => $contract->name,
        ]);
    }

    public function update(Receipt $receipt, ReceiptUpdateRequest $request)
    {
        abort_unless($receipt->is_draft, Response::HTTP_BAD_REQUEST, 'Нельзя обновить пробитый чек');
        abort_if($receipt->user_id !== Auth::id(), Response::HTTP_FORBIDDEN, 'Доступ запрещен');

        $insurer = Insurer::find($request->get('insurer_id'));
        $contract = Contract::find($request->get('contract_id'));

        abort_if($contract->insurer_id !== $insurer->id, Response::HTTP_BAD_REQUEST, 'Контракт не принадлежит выбранному страховщику');

        $receipt->update([
            ...$request->validated(),
            'insurer_name' => $insurer->name,
            'insurer_inn' => $insurer->inn,
            'contract_name' => $contract->name,
        ]);

        return response()->json($receipt);
    }

    public function destroy(Receipt $receipt, ReceiptDestroyRequest $request)
    {
        abort_unless($receipt->is_draft, Response::HTTP_BAD_REQUEST, 'Нельзя удалить пробитый чек');

        $receipt->delete();
    }

    public function submit(ReceiptSubmitRequest $request, AtolService $atol)
    {
        $insurer = Insurer::find($request->get('insurer_id'));
        $contract = Contract::find($request->get('contract_id'));

        abort_if($contract->insurer_id !== $insurer->id, Response::HTTP_BAD_REQUEST, 'Контракт не принадлежит выбранному страховщику');

        if ($request->get('id') !== null) {
            $receipt = Receipt::find($request->get('id'));

            abort_unless($receipt->is_draft, Response::HTTP_BAD_REQUEST, 'Чек уже оформлен');
            abort_if($receipt->user_id !== Auth::id(), Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        } else {
            $receipt = Receipt::fromSubmitRequest($request);
        }

        $receipt->insurer_name = $insurer->name;
        $receipt->insurer_inn = $insurer->inn;
        $receipt->contract_name = $contract->name;
        $receipt->submited_at = now();
        $receipt->is_draft = false;
        $receipt->payment_type = $request->get('payment_type');

        $response = $atol->sell($receipt);

        $receipt->external_id = $response->uuid;
        $receipt->status = $response->status;

        $receipt->save();
    }
}
