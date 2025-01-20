<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
    Route::get('test', function () {});
}

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::get('user', function () {
    if (! auth()->check()) {
        abort(Response::HTTP_UNAUTHORIZED);
    }

    return response()->json(auth()->user());
});

Route::view('reset-password', 'app')->name('password.reset');

Route::fallback(function () {
    return view('app');
});
