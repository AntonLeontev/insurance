<?php

namespace App\Http\Controllers;

use App\DTO\ReceiptsCollectionDTO;
use App\Enums\ReceiptStatus;
use App\Enums\ReceiptType;
use App\Enums\Role;
use App\Http\Requests\ReceiptDestroyRequest;
use App\Http\Requests\ReceiptIndexRequest;
use App\Http\Requests\ReceiptStoreRequest;
use App\Http\Requests\ReceiptSubmitRequest;
use App\Http\Requests\ReceiptUpdateRequest;
use App\Models\Agency;
use App\Models\AgencyUser;
use App\Models\Contract;
use App\Models\Insurer;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\ReceiptDone;
use App\Notifications\ReceiptFail;
use App\Services\Atol\AtolService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): JsonResponse
    {
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $receipt->agency_id)->first();
        if (empty($agencyUser)) {
            abort(Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        }

        if ($agencyUser->role === Role::CASHIER) {
            abort_if($receipt->user_id !== Auth::id(), Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        } else {
            abort_if(Auth::user()->agencies->pluck('id')->doesntContain($receipt->agency_id), Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        }

        return response()->json($receipt->load('user'));
    }

    public function index(ReceiptIndexRequest $request): JsonResponse|ReceiptsCollectionDTO
    {
        $receipts = Receipt::query()
            ->avaliableForUser($request->get('agency_id'))
            ->filters()
            ->sort()
            ->search()
            ->with(['user' => fn ($q) => $q->select(['email', 'name', 'id'])])
            ->paginate($request->get('items_per_page', 100))
            ->withQueryString();

        return new ReceiptsCollectionDTO($receipts);
    }

    public function store(ReceiptStoreRequest $request)
    {
        $insurer = Insurer::find($request->get('insurer_id'));
        $contract = Contract::find($request->get('contract_id'));

        abort_if($contract->insurer_id !== $insurer->id, Response::HTTP_BAD_REQUEST, 'Контракт не принадлежит выбранному страховщику');

        $agency = Agency::find($request->get('agency_id'));

        Receipt::create([
            ...Arr::except($request->validated(), ['agent_email']),
            'agent_email' => $agency->email,
            'user_id' => Auth::id(),
            'insurer_name' => $insurer->name,
            'insurer_inn' => $insurer->inn,
            'contract_name' => $contract->name,
            'vat' => $contract->vat,
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
            'vat' => $contract->vat,
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
        $receipt->vat = $contract->vat;
        $receipt->submited_at = now();
        $receipt->is_draft = false;
        $receipt->payment_type = $request->get('payment_type');

        $response = $atol->sell($receipt, Agency::find($request->get('agency_id')));

        $receipt->external_id = $response->uuid;
        $receipt->status = $response->status;

        $receipt->save();
    }

    public function refund(Receipt $receipt, AtolService $atol)
    {
        abort_if($receipt->status !== ReceiptStatus::DONE, Response::HTTP_BAD_REQUEST, 'Нельзя сделать возврат по неудачному чеку');
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $receipt->agency_id)->first();
        abort_if(empty($agencyUser), Response::HTTP_FORBIDDEN, 'Доступ запрещен');
        abort_if($agencyUser->role === Role::CASHIER, Response::HTTP_FORBIDDEN, 'Доступ запрещен');

        $data = $receipt->toArray();
        $data['submited_at'] = now()->format('d.m.Y H:i:s');
        $data['receipt_type'] = ReceiptType::SELL_REFUND->value;
        $data['parent_id'] = $receipt->id;
        data_forget($data, 'id');
        data_forget($data, 'created_at');
        data_forget($data, 'updated_at');
        data_forget($data, 'external_id');

        $newReceipt = Receipt::create($data);
        $response = $atol->sellRefund($newReceipt, Agency::find($receipt->agency_id));

        $newReceipt->external_id = $response->uuid;
        $newReceipt->status = $response->status;

        $newReceipt->save();
    }

    public function getStatus(Receipt $receipt, AtolService $atol): JsonResponse
    {
        abort_if($receipt->external_id === null, Response::HTTP_BAD_REQUEST, 'Чек не отправлен в Атол');
        $agencyUser = AgencyUser::where('user_id', Auth::id())->where('agency_id', $receipt->agency_id)->first();
        abort_if(empty($agencyUser), Response::HTTP_FORBIDDEN, 'Доступ запрещен');

        if ($receipt->status === ReceiptStatus::DONE || $receipt->status === ReceiptStatus::FAIL) {
            return response()->json($receipt);
        }

        $response = $atol->report($receipt, Agency::find($receipt->agency_id));

        if ($response->json('status') === 'wait') {
            return response()->json($receipt);
        }

        $user = User::find($receipt->user_id);
        $agency = Agency::find($receipt->agency_id);

        if ($response->json('status') === 'fail') {
            $receipt->update([
                'status' => $response->json('status'),
                'error_text' => $response->json('error.text'),
            ]);

            $user->notify(new ReceiptFail($receipt->id));

            if ($agency->receipt_email !== null) {
                Notification::route('mail', $agency->receipt_email)->notify(new ReceiptFail($receipt->id));
            }
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

            $user->notify(new ReceiptDone($receipt->id));

            if ($agency->receipt_email !== null) {
                Notification::route('mail', $agency->receipt_email)->notify(new ReceiptDone($receipt->id));
            }
        }

        return response()->json($receipt);
    }

    public function pdf(Receipt $receipt)
    {
        $agency = Agency::find($receipt->agency_id);

        $time = Carbon::parse($receipt->receipt_datetime)->format('Ymd\THi');
        $data = sprintf('t=%s&s=%s&fn=%s&i=%s&fp=%s&n=%s', $time, $receipt->amount, $receipt->fn_number, $receipt->fiscal_document_number, $receipt->fiscal_document_attribute, $receipt->receipt_type->value === 'sell' ? 1 : 2);

        $options = new QROptions;
        $options->outputBase64 = false;

        $qrcode = base64_encode((new QRCode($options))->render($data));

        $pdf = Pdf::loadView('pdf.receipt', compact('receipt', 'qrcode', 'agency'))
            ->setPaper([0, 0, 360, 1000]);

        // return view('pdf.receipt', compact('receipt', 'qrcode', 'agency'));
        return $pdf->download('receipt.pdf');
    }
}
