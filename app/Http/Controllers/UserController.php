<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordStoreRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function currentUser()
    {
        return response()->json(auth()->user()->load('agencies'));
    }

    public function updateProfile(ProfileUpdateRequest $request)
    {
        auth()->user()->update($request->validated());

        return response()->json(auth()->user());
    }

    public function updatePassword(PasswordUpdateRequest $request)
    {
        auth()->user()->update([
            'password' => bcrypt($request->validated('password')),
        ]);
    }

    public function storePassword(PasswordStoreRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        abort_if($user === null, Response::HTTP_BAD_REQUEST, 'Пароль для пользователя уже установлен');

        if ($user->password !== $request->get('token')) {
            abort(Response::HTTP_BAD_REQUEST, 'Пароль для пользователя уже установлен');
        }

        $user->password = bcrypt($request->validated('password'));
        $user->email_verified_at = now();
        $user->save();

        Auth::login($user);
    }
}
