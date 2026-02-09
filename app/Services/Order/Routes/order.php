<?php

use Illuminate\Support\Facades\Route;
use App\Services\Order\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Order Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
});
