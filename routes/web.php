<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
    Route::get('test', function () {});
}

Route::post('login', [AuthController::class, 'login'])->name('login');

Route::controller(UserController::class)->group(function () {
    Route::get('user', 'currentUser');
    Route::post('user/update', 'updateProfile')->middleware('precognitive')->name('user.update');
    Route::post('user/password', 'updatePassword')->middleware('precognitive')->name('user.password');
});

Route::view('reset-password', 'app')->name('password.reset');

Route::fallback(function () {
    return view('app');
});
