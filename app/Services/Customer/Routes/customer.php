<?php

use Illuminate\Support\Facades\Route;
use App\Services\Customer\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Customer Service API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});
