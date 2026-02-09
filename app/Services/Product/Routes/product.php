<?php

use Illuminate\Support\Facades\Route;
use App\Services\Product\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Product Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/products')->group(function () {
    // Read operations - higher rate limit
    Route::middleware(['api.rate.limit:read'])->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    // Write operations - moderate rate limit
    Route::middleware(['api.rate.limit:write'])->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});
