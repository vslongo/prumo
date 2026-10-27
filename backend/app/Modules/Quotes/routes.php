<?php

declare(strict_types=1);

use App\Modules\Quotes\Http\Controllers\PublicQuoteController;
use App\Modules\Quotes\Http\Controllers\QuoteController;
use App\Modules\Quotes\Http\Middleware\ResolveQuoteFromToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('quotes', QuoteController::class)->only(['index', 'store', 'show']);
    Route::post('quotes/{quote}/items', [QuoteController::class, 'addItem']);
    Route::delete('quotes/{quote}/items/{item}', [QuoteController::class, 'removeItem'])->scopeBindings();
    Route::post('quotes/{quote}/send', [QuoteController::class, 'send']);
});

Route::prefix('public/quotes/{token}')
    ->middleware(['throttle:public-links', ResolveQuoteFromToken::class])
    ->controller(PublicQuoteController::class)
    ->group(function () {
        Route::get('/', 'show');
        Route::post('signature-upload-url', 'signatureUploadUrl')->middleware('throttle:quote-signature');
        Route::post('approve', 'approve');
        Route::post('reject', 'reject');
    });
