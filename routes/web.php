<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AtolWebhookController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\UserController;
use App\Services\Atol\AtolService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
    Route::get('test', function (AtolService $atol) {
        $data = 't=20250409T1351&s=6608.45&fn=7380440801037213&i=133&fp=2728305220&n=1';

        $options = new QROptions;
        $options->outputBase64 = false; // output raw image instead of base64 data URI

        $qrcode = base64_encode((new QRCode($options))->render($data));

        return "<img src='data:image/svg+xml;base64,{$qrcode}' width='400' />";
    });
}

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['throttle:10,5'])
    ->name('login');

Route::controller(UserController::class)->group(function () {
    Route::get('user', 'currentUser')->middleware('auth');
    Route::post('user/update', 'updateProfile')->middleware('precognitive')->name('user.update');
    Route::post('user/password', 'updatePassword')->middleware('precognitive')->name('user.password');
});

Route::controller(AgencyController::class)->group(function () {
    Route::post('agencies/{agency}/update-details', 'updateDetails')->middleware('precognitive')->name('agency.update-details');
    Route::post('agencies/{agency}/update-atol', 'updateAtol')->middleware('precognitive')->name('agency.update-atol');
    Route::get('agencies/{agency}/users', 'users')->name('agencies.users')->can('viewUsers', 'agency');
    Route::delete('agencies/{agency}/users/{id}', 'deleteUser')->name('agencies.users.destroy')->can('deleteUsers', 'agency');
    Route::post('agencies/{agency}/users/', 'createUser')->name('agencies.users.create')->can('createUsers', 'agency');
    Route::post('agencies/{agency}/users/{user}/invite', 'sendInvite')
        ->middleware(['throttle:3,1'])
        ->name('agencies.users.invite')
        ->can('createUsers', 'agency');
});

Route::controller(InsurerController::class)->group(function () {
    Route::get('insurers', 'index')
        ->middleware(['needAgency'])
        ->name('insurers.index');
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
    Route::put('contracts/{contract}', 'update')
        ->middleware(['precognitive'])
        ->name('contracts.update');
    Route::delete('contracts/{contract}', 'destroy')
        ->name('contracts.destroy');
});

Route::controller(ReceiptController::class)->group(function () {
    Route::get('receipts', 'index')
        ->name('receipts.index')
        ->middleware(['needAgency']);
    Route::get('receipts/{receipt}', 'show')
        ->whereUuid('receipt')
        ->name('receipts.show');
    Route::post('receipts', 'store')
        ->middleware(['precognitive', 'needAgency'])
        ->name('receipts.store');
    Route::put('receipts/{receipt}', 'update')
        ->name('receipts.update');
    Route::delete('receipts/{receipt}', 'destroy')
        ->name('receipts.destroy');
    Route::post('receipts/submit', 'submit')
        ->name('receipts.submit');
    Route::get('receipts/{receipt}/get-status', 'getStatus')
        ->name('receipts.get-status');
    Route::post('receipts/{receipt}/refund', 'refund')
        ->name('receipts.refund');
    Route::get('receipts/{receipt}/pdf', 'pdf')
        ->name('receipts.pdf');
    Route::get('receipts/{receipt}/details', 'pdf')
        ->name('receipts.details');
});

Route::post('webhooks/atol', AtolWebhookController::class)->name('webhooks.atol');

Route::view('reset-password', 'app')->name('password.reset');
Route::view('create-password/{email}/{password}', 'app')->name('password.create');
Route::post('create-password', [UserController::class, 'storePassword'])
    ->middleware(['precognitive', 'throttle:10,5'])
    ->name('password.store');

Route::fallback(function () {
    return view('app');
});
