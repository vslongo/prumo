<?php

declare(strict_types=1);

use App\Modules\ServiceOrders\Http\Controllers\ServiceOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->controller(ServiceOrderController::class)->group(function () {
    Route::get('service-orders', 'index');
    Route::post('service-orders', 'store');
    Route::get('service-orders/{serviceOrder}', 'show');
    Route::post('service-orders/{serviceOrder}/items', 'addItem');
    Route::post('service-orders/{serviceOrder}/items/{item}/done', 'markItemDone')->scopeBindings();
    Route::post('service-orders/{serviceOrder}/transition', 'transition');
    Route::post('service-orders/{serviceOrder}/close', 'close');
});
