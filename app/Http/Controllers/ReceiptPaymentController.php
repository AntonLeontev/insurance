<?php

namespace App\Http\Controllers;

use App\Enums\PaymentType;
use App\Models\Agency;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\Atol\AtolService;
use App\Services\Tbank\MerchantApi;
use App\Services\Tbank\MerchantApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReceiptPaymentController extends Controller
{
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

    public function checkout(Receipt $receipt): JsonResponse
    {
        abort_unless($receipt->is_draft, Response::HTTP_NOT_FOUND);

        $agency = Agency::find($receipt->agency_id);
        $credentials = $agency->tbankCredentials;

        abort_if(! $credentials || ! $credentials->terminal || ! $credentials->password, Response::HTTP_BAD_REQUEST, 'Настройки платежной системы не настроены');

        $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();
        if ($payment && $payment->expired_at->isFuture() && $payment->status === 'NEW') {
            return response()->json([
                'redirect_url' => $payment->redirect_url,
            ]);
        }

        $merchantApi = new MerchantApi($credentials->terminal, $credentials->password);
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

        return response()->json([
            'redirect_url' => $response->paymentUrl,
        ]);
    }

    public function paymentSuccess(Receipt $receipt, AtolService $atolService)
    {
        // $payment = Payment::where('receipt_id', $receipt->id)->latest()->first();

        // abort_if(! $payment, Response::HTTP_NOT_FOUND);

        // $agency = Agency::find($receipt->agency_id);
        // $credentials = $agency->tbankCredentials;

        // abort_if(! $credentials || ! $credentials->terminal || ! $credentials->password, Response::HTTP_BAD_REQUEST, 'Настройки платежной системы не настроены');

        // $merchantApi = new MerchantApi($credentials->terminal, $credentials->password);
        // $service = new MerchantApiService($merchantApi);

        // $paymentStatus = $service->getPaymentState($payment->payment_id);

        // if ($paymentStatus === 'CONFIRMED' && $receipt->is_draft) {
        //     $payment->update([
        //         'status' => $paymentStatus,
        //         'paid_at' => now(),
        //     ]);

        //     $receipt->is_draft = false;
        //     $receipt->payment_type = PaymentType::CASHLESS;
        //     $receipt->submited_at = now();

        //     $atolResponse = $atolService->sell($receipt, $agency);

        //     $receipt->external_id = $atolResponse->uuid;
        //     $receipt->status = $atolResponse->status;
        //     $receipt->save();
        // } elseif ($paymentStatus === 'CONFIRMED' && ! $receipt->is_draft) {
        //     // Платеж уже обработан
        //     $payment->update([
        //         'status' => $paymentStatus,
        //         'paid_at' => $payment->paid_at ?? now(),
        //     ]);
        // }

        return view('app');
    }

    public function paymentWebhook(Request $request, Receipt $receipt, AtolService $atolService)
    {
        $agency = Agency::find($receipt->agency_id);
        $credentials = $agency->tbankCredentials;

        abort_if(! $credentials || ! $credentials->terminal || ! $credentials->password, Response::HTTP_BAD_REQUEST, 'Настройки платежной системы не настроены');

        // Проверка токена
        $receivedToken = $request->json('Token');
        $webhookData = $request->all();

        if (! $receivedToken) {
            Log::channel('telegram')->warning('Отсутствует токен в вебхуке от Тинькофф', [
                'receipt_id' => $receipt->id,
                'payment_id' => $request->json('PaymentId'),
            ]);

            return response('Token is required', Response::HTTP_UNAUTHORIZED);
        }

        $merchantApi = new MerchantApi($credentials->terminal, $credentials->password);

        if (! $merchantApi->verifyWebhookToken($webhookData, $receivedToken)) {
            Log::channel('telegram')->warning('Неверный токен в вебхуке от Тинькофф', [
                'receipt_id' => $receipt->id,
                'payment_id' => $request->json('PaymentId'),
            ]);

            return response('Invalid token', Response::HTTP_UNAUTHORIZED);
        }

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

        $payment->update([
            'status' => $request->json('Status'),
        ]);

        if ($request->json('PaymentId') === 'CONFIRMED' && $receipt->is_draft) {
            $receipt->is_draft = false;
            $receipt->payment_type = PaymentType::CASHLESS;
            $receipt->submited_at = now();
            $receipt->save();

            $atolResponse = $atolService->sell($receipt, $agency);

            $receipt->external_id = $atolResponse->uuid;
            $receipt->status = $atolResponse->status;
            $receipt->save();
        }

        return 'OK';
    }
}
