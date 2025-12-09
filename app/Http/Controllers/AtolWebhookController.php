<?php

namespace App\Http\Controllers;

use App\Enums\ReceiptStatus;
use App\Models\Agency;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\ReceiptDone;
use App\Notifications\ReceiptFail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AtolWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $receipt = Receipt::find($request->json('external_id'));

        $user = User::find($receipt->user_id);
        $agency = Agency::find($receipt->agency_id);

        if ($request->json('status') === 'fail') {
            $needNotification = $receipt->status !== ReceiptStatus::FAIL;

            $receipt->update([
                'status' => $request->json('status'),
                'error_text' => $request->json('error.text'),
            ]);

            if ($needNotification) {
                $user->notify(new ReceiptFail($receipt->id));

                if ($agency->receipt_email !== null) {
                    Notification::route('mail', $agency->receipt_email)->notify(new ReceiptFail($receipt->id));
                }
            }
        }

        if ($request->json('status') === 'done') {
            $needNotification = $receipt->status !== ReceiptStatus::DONE;

            $receipt->update([
                'status' => 'done',
                'fiscal_receipt_number' => $request->json('payload.fiscal_receipt_number'),
                'shift_number' => $request->json('payload.shift_number'),
                'receipt_datetime' => $request->json('payload.receipt_datetime'),
                'fn_number' => $request->json('payload.fn_number'),
                'ecr_registration_number' => $request->json('payload.ecr_registration_number'),
                'fiscal_document_number' => $request->json('payload.fiscal_document_number'),
                'fiscal_document_attribute' => $request->json('payload.fiscal_document_attribute'),
                'ofd_receipt_url' => $request->json('payload.ofd_receipt_url'),
            ]);

            if ($needNotification) {
                $user->notify(new ReceiptDone($receipt->id));

                if ($agency->receipt_email !== null) {
                    Notification::route('mail', $agency->receipt_email)->notify(new ReceiptDone($receipt->id));
                }
            }
        }
    }
}
