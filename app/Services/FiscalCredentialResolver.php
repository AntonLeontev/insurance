<?php

namespace App\Services;

use App\Models\FiscalCredential;
use App\Models\Insurer;
use App\Models\Receipt;
use Illuminate\Http\Response;

class FiscalCredentialResolver
{
    public function resolveForInsurer(?Insurer $insurer, int $agencyId): FiscalCredential
    {
        if ($insurer?->fiscal_credential_id !== null) {
            $credential = FiscalCredential::find($insurer->fiscal_credential_id);

            if ($credential !== null) {
                return $credential;
            }
        }

        return $this->resolveDefault($agencyId);
    }

    public function resolveForReceipt(Receipt $receipt): FiscalCredential
    {
        if ($receipt->fiscal_credential_id !== null) {
            $credential = FiscalCredential::withTrashed()->find($receipt->fiscal_credential_id);

            if ($credential !== null) {
                return $credential;
            }
        }

        $insurer = $receipt->insurer_id !== null
            ? Insurer::find($receipt->insurer_id)
            : null;

        return $this->resolveForInsurer($insurer, $receipt->agency_id);
    }

    public function resolveDefault(int $agencyId): FiscalCredential
    {
        $credential = FiscalCredential::where('agency_id', $agencyId)
            ->where('is_default', true)
            ->first();

        abort_if(
            $credential === null,
            Response::HTTP_BAD_REQUEST,
            'Реквизиты по умолчанию не настроены'
        );

        return $credential;
    }
}
