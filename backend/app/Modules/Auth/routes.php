<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\SessionController;
use App\Modules\Auth\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('me', [SessionController::class, 'show']);
    Route::post('logout', [SessionController::class, 'destroy']);
    Route::get('users', [UserController::class, 'index']);
});
