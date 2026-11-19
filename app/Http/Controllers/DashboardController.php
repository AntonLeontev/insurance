<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function show(Request $request, DashboardStats $stats): JsonResponse
    {
        $user = $request->user();

        if ($user->email !== config('services.dashboard.owner_email')) {
            Log::warning('[DashboardController.show] forbidden', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'error' => 'Доступ запрещен',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json($stats->build((int) $request->get('agency_id')));
    }
}
