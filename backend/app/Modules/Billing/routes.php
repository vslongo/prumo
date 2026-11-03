<?php

declare(strict_types=1);

use App\Modules\Billing\Http\Controllers\AsaasWebhookController;
use App\Modules\Billing\Http\Controllers\BillingController;
use App\Modules\Billing\Http\Middleware\VerifyAsaasToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->controller(BillingController::class)->group(function () {
    Route::get('billing/access', 'access');
    Route::get('billing', 'show');
    Route::post('billing/subscribe', 'subscribe');
    Route::patch('billing/plan', 'changePlan');
    Route::post('billing/cancel', 'cancel');
});

Route::post('webhooks/asaas', AsaasWebhookController::class)->middleware(['throttle:60,1', VerifyAsaasToken::class]);
