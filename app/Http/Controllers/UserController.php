<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function currentUser()
    {
        if (! auth()->check()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        return response()->json(auth()->user());
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
}
