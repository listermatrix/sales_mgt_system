<?php

use Illuminate\Support\Facades\Route;
use App\Services\Order\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Order Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/orders')->group(function () {
    // Read operations - higher rate limit
    Route::middleware(['api.rate.limit:read'])->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });

    // Write operations - moderate rate limit
    Route::middleware(['api.rate.limit:write'])->group(function () {
        Route::post('/', [OrderController::class, 'store']);
        Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
