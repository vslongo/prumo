<?php

declare(strict_types=1);

use App\Modules\Vehicles\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('vehicles', VehicleController::class)->only(['index', 'store', 'show']);
    Route::get('customers/{customerId}/vehicles', [VehicleController::class, 'forCustomer']);
});
