<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyUpdateAtolRequest;
use App\Http\Requests\AgencyUpdateDetailsRequest;
use App\Http\Requests\UserCreateRequest;
use App\Models\Agency;
use App\Models\AgencyUser;
use App\Models\User;
use App\Notifications\UserInvited;
use App\Services\Atol\AtolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgencyController extends Controller
{
    public function updateDetails(AgencyUpdateDetailsRequest $request, Agency $agency)
    {
        $agency->update($request->validated());

        return response()->json($agency);
    }

    public function updateAtol(AgencyUpdateAtolRequest $request, AtolService $service, Agency $agency)
    {
        $agency->update([
            'group_code' => $request->validated('group_code'),
            'ffd' => $request->validated('ffd'),
        ]);

        $token = $service->getToken($request->validated('atol_login'), $request->validated('atol_password'), $agency);

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
            ? AgencyUser::where('agency_id', $agency->id)->count()
            : $request->get('items_per_page');

        $usersIds = AgencyUser::where('agency_id', $agency->id)->pluck('user_id')->toArray();

        $users = User::select(['name', 'email', 'id', 'email_verified_at'])
            ->whereIn('id', $usersIds)
            ->with('agencies')
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
        $customPaginator['data'] = $users->getCollection()->map(function ($user) use ($agency) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->agencies->where('id', $agency->id)->first()->pivot->role->name(),
                'email_verified' => $user->email_verified_at !== null,
            ];
        });

        return response()->json($customPaginator);
    }

    public function deleteUser(Agency $agency, int $id)
    {
        AgencyUser::where('user_id', $id)->where('agency_id', $agency->id)->delete();
    }

    public function createUser(UserCreateRequest $request, Agency $agency)
    {
        if (User::where('email', $request->validated('email'))->exists()) {
            $password = '';
            $user = User::where('email', $request->validated('email'))->first();
        } else {
            $password = str()->random(16);
            $user = User::create([
                'password' => $password,
                ...$request->validated(),
            ]);
        }

        $user->agencies()->attach($agency->id, ['role' => $request->validated('role')]);

        $user->notify(new UserInvited($password, $agency->id));
    }

    public function sendInvite(Agency $agency, User $user)
    {
        if ($user->email_verified_at !== null) {
            abort(Response::HTTP_BAD_REQUEST, 'Пользователь уже принял приглашение');
        }

        $token = str()->random(16);
        $user->password = $token;
        $user->save();

        $user->notify(new UserInvited($token, $agency->id));
    }
}
