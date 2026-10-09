<?php

namespace App\Http\Controllers;

use App\Enums\PaymentType;
use App\Models\FiscalCredential;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use App\Services\FiscalCredentialResolver;
use App\Services\GoogleSheets\AppendPaymentRowToGoogleSheet;
use App\Services\Tbank\Exceptions\MerchantApiException;
use App\Services\Tbank\MerchantApi;
use App\Services\Tbank\MerchantApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
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

        if ($credential->sbp_only) {
            return response()->json([
                'sbp' => true,
            ]);
        }

        $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();
        if ($payment && $payment->expired_at->isFuture() && $payment->status === 'NEW') {
            return response()->json([
                'redirect_url' => $payment->redirect_url,
            ]);
        }

        $service = $this->merchantService($credential);
        $payment = $this->createInitializedPayment($receipt, $service);
        $this->deferAppendPaymentRow($appendPaymentRowToGoogleSheet, $payment, $receipt, $credential);

        return response()->json([
            'redirect_url' => $payment->redirect_url,
        ]);
    }

    public function sbpQr(Receipt $receipt, AppendPaymentRowToGoogleSheet $appendPaymentRowToGoogleSheet): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        $credential = $this->resolvePayableCredential($receipt);
        $service = $this->merchantService($credential);

        $payment = $this->reusableNewPayment($receipt);

        if (! $payment) {
            $payment = $this->createInitializedPayment($receipt, $service);
            $this->deferAppendPaymentRow($appendPaymentRowToGoogleSheet, $payment, $receipt, $credential);
        }

        try {
            $qrSvg = $service->getSbpQrSvg((string) $payment->payment_id);
        } catch (MerchantApiException $exception) {
            Log::channel('payments')->error('Не удалось получить QR СБП', [
                'receipt_id' => $receipt->id,
                'payment_id' => $payment->payment_id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Не удалось получить QR для оплаты по СБП. Проверьте, что СБП включён на терминале.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'qr_svg' => $qrSvg,
            'payment_id' => $payment->payment_id,
        ]);
    }

    public function sbpBanks(Request $request, Receipt $receipt): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'device' => ['required', 'in:desktop,mobile'],
        ]);

        $credential = $this->resolvePayableCredential($receipt);
        $service = $this->merchantService($credential);
        $device = $validated['device'];

        try {
            $banks = Cache::remember(
                "sbp-banks:{$credential->terminal}:{$device}",
                now()->addDay(),
                fn () => $service->getSbpBankList($device),
            );
        } catch (MerchantApiException $exception) {
            Log::channel('payments')->error('Не удалось получить список банков СБП', [
                'receipt_id' => $receipt->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Не удалось получить список банков для оплаты по СБП.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'banks' => $banks,
        ]);
    }

    public function sbpDeeplink(Request $request, Receipt $receipt): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'bank_id' => ['required', 'string'],
        ]);

        $credential = $this->resolvePayableCredential($receipt);
        $payment = $this->reusableNewPayment($receipt);

        if (! $payment) {
            return response()->json([
                'message' => 'Сначала инициируйте оплату',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service = $this->merchantService($credential);

        try {
            $deeplink = $service->getSbpDeeplink((string) $payment->payment_id, $validated['bank_id']);
        } catch (MerchantApiException $exception) {
            Log::channel('payments')->error('Не удалось получить deeplink СБП', [
                'receipt_id' => $receipt->id,
                'payment_id' => $payment->payment_id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Не удалось открыть оплату в приложении банка.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'deeplink' => $deeplink,
        ]);
    }

    public function paymentStatus(Request $request, Receipt $receipt): JsonResponse
    {
        $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();
        $status = $payment?->status;

        $shouldSync = $payment
            && $payment->payment_id
            && $payment->status === 'NEW'
            && (
                $request->boolean('sync')
                || $payment->created_at->lte(now()->subMinutes(5))
            );

        if ($shouldSync && ($request->boolean('sync') || Cache::add("payment-getstate:{$payment->id}", true, now()->addMinutes(2)))) {
            $status = $this->syncPaymentStatus($receipt, $payment) ?? $status;
        }

        return response()->json([
            'status' => $status,
            'is_draft' => $receipt->is_draft,
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

    private function resolvePayableCredential(Receipt $receipt): FiscalCredential
    {
        $credential = $this->credentialResolver->resolveForReceipt($receipt);

        abort_if(
            ! $credential->hasPaymentTerminal(),
            Response::HTTP_BAD_REQUEST,
            'Настройки платежной системы не настроены для выбранной страховой'
        );

        return $credential;
    }

    private function merchantService(FiscalCredential $credential): MerchantApiService
    {
        return new MerchantApiService(new MerchantApi($credential->terminal, $credential->password));
    }

    private function reusableNewPayment(Receipt $receipt): ?Payment
    {
        $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();

        if (
            $payment
            && $payment->status === 'NEW'
            && $payment->payment_id
            && $payment->expired_at->isFuture()
        ) {
            return $payment;
        }

        return null;
    }

    private function createInitializedPayment(Receipt $receipt, MerchantApiService $service): Payment
    {
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

        return $payment->fresh();
    }

    private function deferAppendPaymentRow(
        AppendPaymentRowToGoogleSheet $appendPaymentRowToGoogleSheet,
        Payment $payment,
        Receipt $receipt,
        FiscalCredential $credential,
    ): void {
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
    }

    private function syncPaymentStatus(Receipt $receipt, Payment $payment): ?string
    {
        $credential = $this->credentialResolver->resolveForReceipt($receipt);

        if (! $credential->hasPaymentTerminal()) {
            return $payment->status;
        }

        try {
            $remoteStatus = $this->merchantService($credential)->getPaymentState((string) $payment->payment_id);
        } catch (MerchantApiException $exception) {
            Log::channel('payments')->warning('Не удалось получить статус платежа через GetState', [
                'receipt_id' => $receipt->id,
                'payment_id' => $payment->payment_id,
                'message' => $exception->getMessage(),
            ]);

            return $payment->status;
        }

        if ($remoteStatus === '' || $remoteStatus === $payment->status || $remoteStatus === 'CONFIRMED') {
            return $remoteStatus === 'CONFIRMED' ? 'CONFIRMED' : $payment->status;
        }

        $payment->update(['status' => $remoteStatus]);

        return $remoteStatus;
    }
}
