<?php

declare(strict_types=1);

use App\Modules\Customers\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update']);
    Route::get('customers/{customer}/export', [CustomerController::class, 'export']);
    Route::post('customers/{customer}/anonymize', [CustomerController::class, 'anonymize']);
});
