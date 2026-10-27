<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Login de SPA via cookie (Sanctum). 2FA do owner entra na próxima etapa. */
final class SessionController
{
    public function store(LoginRequest $request): UserResource
    {
        $credentials = $request->validated();

        if (! Auth::guard('web')->attempt([...$credentials, 'is_active' => true])) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        $request->session()->regenerate();

        return new UserResource($request->user()?->load('tenant'));
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()?->load('tenant'));
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }
}
