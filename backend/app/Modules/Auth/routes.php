<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\InvitationController;
use App\Modules\Auth\Http\Controllers\SessionController;
use App\Modules\Auth\Http\Controllers\SignUpController;
use App\Modules\Auth\Http\Controllers\UserController;
use App\Modules\Auth\Http\Middleware\ResolveInvitationFromToken;
use Illuminate\Support\Facades\Route;

Route::post('login', [SessionController::class, 'store'])->middleware('throttle:login');
Route::post('signup', [SignUpController::class, 'store'])->middleware('throttle:signup');

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('me', [SessionController::class, 'show']);
    Route::post('logout', [SessionController::class, 'destroy']);
    Route::get('users', [UserController::class, 'index']);
    Route::post('user-invitations', [InvitationController::class, 'store']);
});

Route::prefix('public/invitations/{token}')
    ->middleware(['throttle:public-links', ResolveInvitationFromToken::class])
    ->controller(InvitationController::class)
    ->group(function () {
        Route::get('/', 'show');
        Route::post('accept', 'accept');
    });
