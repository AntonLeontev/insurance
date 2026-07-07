<?php

namespace App\Http\Controllers;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use App\Services\FiscalCredentialResolver;
use App\Services\GoogleSheets\AppendPaymentRowToGoogleSheet;
use App\Services\Tbank\MerchantApi;
use App\Services\Tbank\MerchantApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReceiptPaymentController extends Controller
{
    public function __construct(private FiscalCredentialResolver $credentialResolver) {}

    public function checkoutPage(Receipt $receipt)
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        return view('app');
    }

    public function checkoutData(Receipt $receipt): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        return response()->json($receipt);
    }

    public function checkout(Receipt $receipt, AppendPaymentRowToGoogleSheet $appendPaymentRowToGoogleSheet): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        $credential = $this->credentialResolver->resolveForReceipt($receipt);

        abort_if(
            ! $credential->hasPaymentTerminal(),
            Response::HTTP_BAD_REQUEST,
            'Настройки платежной системы не настроены для выбранной страховой'
        );

        $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();
        if ($payment && $payment->expired_at->isFuture() && $payment->status === 'NEW') {
            return response()->json([
                'redirect_url' => $payment->redirect_url,
            ]);
        }

        $merchantApi = new MerchantApi($credential->terminal, $credential->password);
        $service = new MerchantApiService($merchantApi);

        $dueDate = now()->addDays(7);

        $payment = Payment::create([
            'receipt_id' => $receipt->id,
            'expired_at' => $dueDate,
        ]);

        $response = $service->initPayment($receipt, $dueDate, $payment->id);

        $payment->update([
            'payment_id' => $response->paymentId,
            'status' => $response->status,
            'redirect_url' => $response->paymentUrl,
        ]);

        defer(function () use ($appendPaymentRowToGoogleSheet, $payment, $receipt, $credential) {
            try {
                $appendPaymentRowToGoogleSheet->append($payment, $receipt, $credential);
            } catch (\Throwable $e) {
                Log::error('Не удалось записать платёж в Google Таблицу', [
                    'payment_id' => $payment->id,
                    'receipt_id' => $receipt->id,
                    'exception' => $e,
                ]);
            }
        });

        return response()->json([
            'redirect_url' => $response->paymentUrl,
        ]);
    }

    public function paymentSuccess(Receipt $receipt, AtolService $atolService)
    {
        return view('app');
    }

    public function paymentWebhook(Request $request, Receipt $receipt, AtolService $atolService)
    {
        $credential = $this->credentialResolver->resolveForReceipt($receipt);

        abort_if(
            ! $credential->hasPaymentTerminal(),
            Response::HTTP_BAD_REQUEST,
            'Настройки платежной системы не настроены'
        );

        $receivedToken = $request->json('Token');
        $webhookData = $request->all();

        if (! $receivedToken) {
            Log::channel('telegram')->warning('Отсутствует токен в вебхуке от Тинькофф', [
                'receipt_id' => $receipt->id,
                'payment_id' => $request->json('PaymentId'),
            ]);

            return response('Token is required', Response::HTTP_UNAUTHORIZED);
        }

        $merchantApi = new MerchantApi($credential->terminal, $credential->password);

        if (! $merchantApi->verifyWebhookToken($webhookData, $receivedToken)) {
            Log::channel('telegram')->warning('Неверный токен в вебхуке от Тинькофф', [
                'receipt_id' => $receipt->id,
                'payment_id' => $request->json('PaymentId'),
            ]);

            return response('Invalid token', Response::HTTP_UNAUTHORIZED);
        }

        Log::channel('payments')->info('Вебхук от тбанка', [
            'receipt_id' => $receipt->id,
            'payment_id' => $request->json('OrderId'),
            'tbank_payment_id' => $request->json('PaymentId'),
            'status' => $request->json('Status'),
            'amount' => $request->json('Amount'),
            'success' => $request->json('Success'),
        ]);

        $payment = Payment::where('payment_id', $request->json('PaymentId'))->first();

        if (! $payment) {
            Log::channel('telegram')->info('Payment not found. Вебхук от тбанка', [
                'payment_id' => $request->json('PaymentId'),
                'status' => $request->json('Status'),
            ]);

            return 'OK';
        }

        if ($payment->status === 'CONFIRMED') {
            return 'OK';
        }

        $data = ['status' => $request->json('Status')];

        if ($request->json('Status') === 'CONFIRMED') {
            $data['paid_at'] = now();
        }

        $payment->update($data);

        if ($request->json('Status') === 'CONFIRMED' && $receipt->is_draft) {
            $receipt->is_draft = false;
            $receipt->payment_type = PaymentType::CASHLESS;
            $receipt->submited_at = now();
            $receipt->fiscal_credential_id = $credential->id;
            $receipt->agent_email = $credential->email;
            $receipt->save();

            $atolResponse = $atolService->sell($receipt, $credential);

            $receipt->external_id = $atolResponse->uuid;
            $receipt->status = $atolResponse->status;
            $receipt->save();
        }

        return 'OK';
    }
}
