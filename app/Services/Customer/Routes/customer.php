<?php

use Illuminate\Support\Facades\Route;
use App\Services\Customer\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Customer Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/customers')->group(function () {
    // Read operations - higher rate limit
    Route::middleware(['api.rate.limit:read'])->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::get('/{id}', [CustomerController::class, 'show']);
    });

    // Write operations - moderate rate limit
    Route::middleware(['api.rate.limit:write'])->group(function () {
        Route::post('/', [CustomerController::class, 'store']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });
});
