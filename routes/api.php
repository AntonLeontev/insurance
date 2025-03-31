<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->middleware(['needAgency']);
});
