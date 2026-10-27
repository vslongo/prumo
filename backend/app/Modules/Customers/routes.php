<?php

declare(strict_types=1);

use App\Modules\Customers\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'show']);
});
