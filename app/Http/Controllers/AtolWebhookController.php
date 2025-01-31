<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class AtolWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $receipt = Receipt::find($request->json('external_id'));

        if ($request->json('status') === 'fail') {
            $receipt->update([
                'status' => $request->json('status'),
                'error_text' => $request->json('error.text'),
            ]);
        }

        if ($request->json('status') === 'done') {
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
        }
    }
}
