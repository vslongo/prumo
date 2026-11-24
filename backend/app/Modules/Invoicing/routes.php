<?php

declare(strict_types=1);

use App\Modules\Invoicing\Http\Controllers\FiscalProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->controller(FiscalProfileController::class)->group(function () {
    Route::get('fiscal-profile', 'show');
    Route::put('fiscal-profile', 'update');
    Route::post('fiscal-profile/certificate/upload-url', 'requestCertificateUpload')->middleware('throttle:10,1');
    Route::post('fiscal-profile/certificate', 'confirmCertificate')->middleware('throttle:10,1');
    Route::delete('fiscal-profile/certificate', 'removeCertificate');
});
