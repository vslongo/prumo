<?php

declare(strict_types=1);

use App\Modules\ServiceTypes\Http\Controllers\ServiceTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('service-types', ServiceTypeController::class)->only(['index', 'store', 'update']);
});
