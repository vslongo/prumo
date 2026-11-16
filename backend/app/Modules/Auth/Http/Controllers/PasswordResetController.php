<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\RequestPasswordReset;
use App\Modules\Auth\Actions\ResetPassword;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

/** "Esqueci minha senha": pede o link por e-mail e troca a senha com o token (ADR 0007). */
final class PasswordResetController
{
    /** Sempre 204, tenha o e-mail cadastro ou não. */
    public function store(ForgotPasswordRequest $request, RequestPasswordReset $requestReset): JsonResponse
    {
        $requestReset->handle($request->validated('email'));

        return response()->json(status: 204);
    }

    public function update(ResetPasswordRequest $request, ResetPassword $reset): JsonResponse
    {
        $reset->handle($request->validated('email'), $request->validated('token'), $request->validated('password'));

        return response()->json(status: 204);
    }
}
