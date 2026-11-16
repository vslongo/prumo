<?php

declare(strict_types=1);

use App\Modules\Quotes\Http\Controllers\PublicQuoteController;
use App\Modules\Quotes\Http\Controllers\QuoteController;
use App\Modules\Quotes\Http\Controllers\QuotePhotoController;
use App\Modules\Quotes\Http\Middleware\ResolveQuoteFromToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('quotes', QuoteController::class)->only(['index', 'store', 'show']);
    Route::post('quotes/{quote}/items', [QuoteController::class, 'addItem']);
    Route::delete('quotes/{quote}/items/{item}', [QuoteController::class, 'removeItem'])->scopeBindings();
    Route::post('quotes/{quote}/send', [QuoteController::class, 'send']);

    Route::controller(QuotePhotoController::class)->scopeBindings()->group(function () {
        Route::get('quotes/{quote}/photos', 'index');
        Route::post('quotes/{quote}/items/{item}/photos', 'store');
        Route::post('quotes/{quote}/items/{item}/photos/{media}/confirm', 'confirm')->whereUuid('media');
        Route::delete('quotes/{quote}/items/{item}/photos/{media}', 'destroy')->whereUuid('media');
    });
});

Route::prefix('public/quotes/{token}')
    ->middleware(['throttle:public-links', ResolveQuoteFromToken::class])
    ->controller(PublicQuoteController::class)
    ->group(function () {
        Route::get('/', 'show');
        Route::get('photos', 'photos');
        Route::post('signature-upload-url', 'signatureUploadUrl')->middleware('throttle:quote-signature');
        Route::post('approve', 'approve');
        Route::post('reject', 'reject');
    });
