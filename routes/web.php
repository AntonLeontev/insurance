<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
}

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['throttle:10,5'])
    ->name('login');

Route::controller(UserController::class)->group(function () {
    Route::get('user', 'currentUser');
    Route::post('user/update', 'updateProfile')->middleware('precognitive')->name('user.update');
    Route::post('user/password', 'updatePassword')->middleware('precognitive')->name('user.password');
});

Route::controller(AgencyController::class)->group(function () {
    Route::post('agency/update-details', 'updateDetails')->middleware('precognitive')->name('agency.update-details');
    Route::post('agency/update-atol', 'updateAtol')->middleware('precognitive')->name('agency.update-atol');
    Route::get('agencies/{agency}/users', 'users')->name('agencies.users')->can('viewUsers', 'agency');
    Route::delete('agencies/{agency}/users/{id}', 'deleteUser')->name('agencies.users.destroy')->can('deleteUsers', 'agency');
    Route::post('agencies/{agency}/users/', 'createUser')->name('agencies.users.create')->can('createUsers', 'agency');
});

Route::view('reset-password', 'app')->name('password.reset');
Route::view('create-password/{email}/{password}', 'app')->name('password.create');
Route::post('create-password', [UserController::class, 'storePassword'])
    ->middleware(['precognitive', 'throttle:10,5'])
    ->name('password.store');

Route::fallback(function () {
    return view('app');
});
