<?php

namespace App\Http\Controllers;

use App\Enums\Ffd;
use App\Http\Requests\FiscalCredentialDestroyRequest;
use App\Http\Requests\FiscalCredentialStoreRequest;
use App\Http\Requests\FiscalCredentialUpdateRequest;
use App\Models\FiscalCredential;
use App\Models\Insurer;
use App\Services\Atol\AtolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FiscalCredentialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $credentials = FiscalCredential::where('agency_id', $request->get('agency_id'))
            ->with(['insurers' => fn ($q) => $q->select(['id', 'name', 'fiscal_credential_id'])])
            // ->orderByDesc('is_default')
            // ->orderBy('name')
            ->get();

        return response()->json($credentials);
    }

    public function store(FiscalCredentialStoreRequest $request, AtolService $atolService): JsonResponse
    {
        $data = $request->validated();
        $insurerIds = $data['insurer_ids'] ?? [];
        unset($data['insurer_ids']);

        $credential = DB::transaction(function () use ($request, $data, $insurerIds, $atolService) {
            $isDefault = $data['is_default'] ?? false;
            $hasDefault = FiscalCredential::where('agency_id', $request->get('agency_id'))
                ->where('is_default', true)
                ->exists();

            if (! $hasDefault) {
                $isDefault = true;
            }

            if ($isDefault) {
                FiscalCredential::where('agency_id', $request->get('agency_id'))
                    ->update(['is_default' => false]);
            }

            $token = $atolService->getToken(
                $data['atol_login'],
                $data['atol_password'],
                \App\Enums\Ffd::from($data['ffd'])->ApiVersion()
            );

            $credential = FiscalCredential::create([
                ...$data,
                'is_default' => $isDefault,
                'atol_token' => $token,
                'atol_token_expires' => now()->addHours(24),
            ]);

            $this->syncInsurers($credential, $insurerIds, $request->get('agency_id'));

            Log::info('FiscalCredential created', [
                'fiscal_credential_id' => $credential->id,
                'agency_id' => $credential->agency_id,
                'is_default' => $credential->is_default,
            ]);

            return $credential;
        });

        return response()->json($credential->load('insurers'));
    }

    public function update(FiscalCredentialUpdateRequest $request, FiscalCredential $fiscalCredential, AtolService $atolService): JsonResponse
    {
        $data = $request->validated();
        $insurerIds = $data['insurer_ids'] ?? null;
        unset($data['insurer_ids']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (empty($data['atol_password'])) {
            unset($data['atol_password']);
        }

        if (
            isset($data['atol_login'], $data['atol_password'], $data['ffd'])
            && ($data['atol_login'] !== $fiscalCredential->atol_login
                || $data['atol_password'] !== $fiscalCredential->atol_password)
        ) {
            $token = $atolService->getToken(
                $data['atol_login'],
                $data['atol_password'],
                Ffd::from($data['ffd'])->ApiVersion()
            );

            $data['atol_token'] = $token;
            $data['atol_token_expires'] = now()->addHours(24);
        }

        $fiscalCredential->update($data);

        if ($insurerIds !== null) {
            $this->syncInsurers($fiscalCredential, $insurerIds, $fiscalCredential->agency_id);
        }

        return response()->json($fiscalCredential->fresh()->load('insurers'));
    }

    public function destroy(FiscalCredential $fiscalCredential, FiscalCredentialDestroyRequest $request): JsonResponse
    {
        if ($fiscalCredential->is_default) {
            abort(Response::HTTP_BAD_REQUEST, 'Нельзя удалить реквизиты по умолчанию. Сначала назначьте другие реквизиты по умолчанию.');
        }

        if ($fiscalCredential->insurers()->exists()) {
            abort(Response::HTTP_BAD_REQUEST, 'Нельзя удалить реквизиты с привязанными страховыми. Сначала отвяжите страховые компании.');
        }

        $fiscalCredential->delete();

        Log::info('FiscalCredential soft deleted', [
            'fiscal_credential_id' => $fiscalCredential->id,
        ]);

        return response()->json(['message' => 'Реквизиты удалены']);
    }

    public function setDefault(FiscalCredential $fiscalCredential): JsonResponse
    {
        $agencyUser = \App\Models\AgencyUser::where('user_id', auth()->id())
            ->where('agency_id', $fiscalCredential->agency_id)
            ->first();

        abort_if(
            $agencyUser === null || $agencyUser->role !== \App\Enums\Role::ADMIN,
            Response::HTTP_FORBIDDEN,
            'Доступ запрещен'
        );

        DB::transaction(function () use ($fiscalCredential) {
            FiscalCredential::where('agency_id', $fiscalCredential->agency_id)
                ->update(['is_default' => false]);

            $fiscalCredential->update(['is_default' => true]);
        });

        Log::info('FiscalCredential set as default', [
            'fiscal_credential_id' => $fiscalCredential->id,
        ]);

        return response()->json($fiscalCredential->fresh()->load('insurers'));
    }

    private function syncInsurers(FiscalCredential $credential, array $insurerIds, int $agencyId): void
    {
        Insurer::where('fiscal_credential_id', $credential->id)
            ->whereNotIn('id', $insurerIds)
            ->update(['fiscal_credential_id' => null]);

        if ($insurerIds === []) {
            return;
        }

        Insurer::where('agency_id', $agencyId)
            ->whereIn('id', $insurerIds)
            ->update(['fiscal_credential_id' => $credential->id]);
    }
}
