<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyUpdateAtolRequest;
use App\Http\Requests\AgencyUpdateDetailsRequest;
use App\Http\Requests\UserCreateRequest;
use App\Models\Agency;
use App\Models\User;
use App\Notifications\UserInvited;
use App\Services\Atol\AtolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgencyController extends Controller
{
    public function updateDetails(AgencyUpdateDetailsRequest $request)
    {
        $agency = auth()->user()->agency;
        $agency->update($request->validated());

        return response()->json($agency);
    }

    public function updateAtol(AgencyUpdateAtolRequest $request, AtolService $service)
    {
        $agency = auth()->user()->agency;
        $agency->update([
            'group_code' => $request->validated('group_code'),
            'ffd' => $request->validated('ffd'),
        ]);

        $token = $service->getToken($request->validated('atol_login'), $request->validated('atol_password'));

        $data = [
            ...$request->validated(),
            'atol_token' => $token,
            'atol_token_expires' => now()->addHours(24),
        ];

        $agency->update($data);
    }

    public function users(Agency $agency, Request $request): JsonResponse
    {
        $paginationSize = $request->get('items_per_page') == -1
            ? User::where('agency_id', $agency->id)->count()
            : $request->get('items_per_page');

        $users = User::select(['name', 'email', 'role', 'id', 'email_verified_at'])
            ->where('agency_id', $agency->id)
            ->when($request->has('sort'), function ($query) use ($request) {
                foreach ($request->get('sort') as $sort) {
                    $query->orderBy($sort['key'], $sort['order']);
                }
            })
            ->when(! $request->has('sort'), function ($query) {
                $query->orderBy('id', 'desc');
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->get('search').'%')
                        ->orWhere('email', 'like', '%'.$request->get('search').'%');
                });
            })
            ->paginate($paginationSize);

        $customPaginator = $users->toArray();
        $customPaginator['data'] = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name(),
                'email_verified' => $user->email_verified_at !== null,
            ];
        });

        return response()->json($customPaginator);
    }

    public function deleteUser(Agency $agency, int $id)
    {
        User::where('id', $id)->where('agency_id', $agency->id)->delete();
    }

    public function createUser(UserCreateRequest $request, Agency $agency)
    {
        $password = str()->random(16);

        $user = User::create([
            'agency_id' => $agency->id,
            'password' => $password,
            ...$request->validated(),
        ]);

        $user->notify(new UserInvited($password));
    }

    public function sendInvite(Agency $agency, User $user)
    {
        if ($user->email_verified_at !== null) {
            abort(Response::HTTP_BAD_REQUEST, 'Пользователь уже принял приглашение');
        }
        if ($user->agency_id !== $agency->id) {
            abort(Response::HTTP_BAD_REQUEST, 'Нельзя пригласить пользователя другого агенства');
        }

        $token = str()->random(16);
        $user->password = $token;
        $user->save();

        $user->notify(new UserInvited($token));
    }
}
