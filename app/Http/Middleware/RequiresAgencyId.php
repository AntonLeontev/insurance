<?php

namespace App\Http\Middleware;

use App\Models\AgencyUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequiresAgencyId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->get('agency_id') === null) {
            return response()->json([
                'error' => 'Отсутствует обязательный параметр agency_id',
            ], Response::HTTP_BAD_REQUEST);
        }

        $userHasAccess = AgencyUser::where('user_id', Auth::id())->where('agency_id', $request->get('agency_id'))->exists();

        if (! $userHasAccess) {
            return response()->json([
                'error' => 'Доступ к данному агентству запрещен',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
