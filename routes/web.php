<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\ReceiptController;
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
    Route::post('agencies/{agency}/users/{user}/invite', 'sendInvite')
        ->middleware(['throttle:3,1'])
        ->name('agencies.users.invite')
        ->can('createUsers', 'agency');
});

Route::controller(InsurerController::class)->group(function () {
    Route::get('insurers', 'index')->name('insurers.index');
    Route::post('insurers', 'store')
        ->middleware(['precognitive'])
        ->name('insurers.store');
    Route::post('insurers/update', 'update')
        ->middleware(['precognitive'])
        ->name('insurers.update');
    Route::delete('insurers/{insurer}', 'destroy')
        ->name('insurers.destroy');
});

Route::controller(ContractController::class)->group(function () {
    Route::post('contracts', 'store')
        ->middleware(['precognitive'])
        ->name('contracts.store');
    // Route::post('contracts/update', 'update')
    // 	->middleware(['precognitive'])
    // 	->name('contracts.update');
    Route::delete('contracts/{contract}', 'destroy')
        ->name('contracts.destroy');
});

Route::controller(ReceiptController::class)->group(function () {
    Route::get('receipts', 'index')
        ->name('receipts.index');
    Route::get('receipts/{receipt}', 'show')
        ->whereNumber('receipt')
        ->name('receipts.show');
    Route::post('receipts', 'store')
        ->middleware(['precognitive'])
        ->name('receipts.store');
    Route::put('receipts/{receipt}', 'update')
        ->name('receipts.update');
    Route::delete('receipts/{receipt}', 'destroy')
        ->name('receipts.destroy');
});

Route::view('reset-password', 'app')->name('password.reset');
Route::view('create-password/{email}/{password}', 'app')->name('password.create');
Route::post('create-password', [UserController::class, 'storePassword'])
    ->middleware(['precognitive', 'throttle:10,5'])
    ->name('password.store');

Route::fallback(function () {
    return view('app');
});
