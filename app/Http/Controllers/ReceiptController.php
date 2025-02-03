<?php

namespace App\Http\Controllers;

use App\Enums\ReceiptStatus;
use App\Enums\ReceiptType;
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

        return response()->json($receipt->load('user'));
    }

    public function index(Request $request): JsonResponse
    {
        $receipts = Receipt::query()
            ->avaliableForUser()
            ->filters()
            ->sort()
            ->search()
            ->with(['user' => fn ($q) => $q->select(['email', 'name', 'id'])])
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

    public function refund(Receipt $receipt, AtolService $atol)
    {
        abort_if($receipt->status !== ReceiptStatus::DONE, Response::HTTP_BAD_REQUEST, 'Нельзя сделать возврат по неудачному чеку');
        abort_if(Auth::user()->agency_id !== $receipt->agency_id, Response::HTTP_FORBIDDEN, 'Нет доступа');
        abort_if(Auth::user()->role === Role::CASHIER, Response::HTTP_FORBIDDEN, 'Нет доступа');

        $data = $receipt->toArray();
        $data['submited_at'] = now()->format('d.m.Y H:i:s');
        $data['receipt_type'] = ReceiptType::SELL_REFUND->value;
        data_forget($data, 'id');
        data_forget($data, 'created_at');
        data_forget($data, 'updated_at');
        data_forget($data, 'external_id');

        $newReceipt = Receipt::create($data);
        $response = $atol->sellRefund($newReceipt);

        $newReceipt->external_id = $response->uuid;
        $newReceipt->status = $response->status;

        $newReceipt->save();
    }

    public function getStatus(Receipt $receipt, AtolService $atol): JsonResponse
    {
        abort_if($receipt->external_id === null, Response::HTTP_BAD_REQUEST, 'Чек не отправлен в Атол');
        abort_if(Auth::user()->agency_id !== $receipt->agency_id, Response::HTTP_FORBIDDEN, 'Доступ запрещен');

        if ($receipt->status === ReceiptStatus::DONE || $receipt->status === ReceiptStatus::FAIL) {
            return response()->json($receipt);
        }

        $response = $atol->report($receipt);

        if ($response->json('status') === 'wait') {
            return response()->json($receipt);
        }

        if ($response->json('status') === 'fail') {
            $receipt->update([
                'status' => $response->json('status'),
                'error_text' => $response->json('error.text'),
            ]);
        }

        if ($response->json('status') === 'done') {
            $receipt->update([
                'status' => 'done',
                'fiscal_receipt_number' => $response->json('payload.fiscal_receipt_number'),
                'shift_number' => $response->json('payload.shift_number'),
                'receipt_datetime' => $response->json('payload.receipt_datetime'),
                'fn_number' => $response->json('payload.fn_number'),
                'ecr_registration_number' => $response->json('payload.ecr_registration_number'),
                'fiscal_document_number' => $response->json('payload.fiscal_document_number'),
                'fiscal_document_attribute' => $response->json('payload.fiscal_document_attribute'),
                'ofd_receipt_url' => $response->json('payload.ofd_receipt_url'),
            ]);
        }

        return response()->json($receipt);
    }
}
