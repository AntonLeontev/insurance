<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Services\Atol\AtolService;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
    Route::get('test', function (AtolService $service) {
        dd($service->getToken());
    });
}

Route::post('login', [AuthController::class, 'login'])->name('login');

Route::controller(UserController::class)->group(function () {
    Route::get('user', 'currentUser');
    Route::post('user/update', 'updateProfile')->middleware('precognitive')->name('user.update');
    Route::post('user/password', 'updatePassword')->middleware('precognitive')->name('user.password');
});

Route::controller(AgencyController::class)->group(function () {
    Route::post('agency/update-details', 'updateDetails')->middleware('precognitive')->name('agency.update-details');
    Route::post('agency/update-atol', 'updateAtol')->middleware('precognitive')->name('agency.update-atol');
});

Route::view('reset-password', 'app')->name('password.reset');

Route::fallback(function () {
    return view('app');
});
