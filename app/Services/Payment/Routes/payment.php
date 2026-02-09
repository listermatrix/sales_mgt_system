<?php

use Illuminate\Support\Facades\Route;
use App\Services\Payment\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Payment Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/payments')->group(function () {
    // Read operations - higher rate limit
    Route::middleware(['api.rate.limit:read'])->group(function () {
        Route::get('/gateways', [PaymentController::class, 'gateways']);
        Route::get('/{id}', [PaymentController::class, 'show']);
    });

    // Payment operations - strict rate limit
    Route::middleware(['api.rate.limit:payment'])->group(function () {
        Route::post('/', [PaymentController::class, 'store']);
        Route::post('/{id}/verify', [PaymentController::class, 'verify']);
        Route::post('/{id}/refund', [PaymentController::class, 'refund']);
    });
});
