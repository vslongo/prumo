<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\ConfirmTwoFactor;
use App\Modules\Auth\Actions\DisableTwoFactor;
use App\Modules\Auth\Actions\EnableTwoFactor;
use App\Modules\Auth\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Verificação em duas etapas da própria conta (qualquer papel; obrigatória para o dono). */
final class TwoFactorController
{
    public function store(Request $request, EnableTwoFactor $enable): JsonResponse
    {
        return response()->json(['data' => $enable->handle($this->user($request))]);
    }

    public function confirm(Request $request, ConfirmTwoFactor $confirm): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        return response()->json(['data' => ['recovery_codes' => $confirm->handle($this->user($request), $request->string('code')->toString())]]);
    }

    public function destroy(Request $request, DisableTwoFactor $disable): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);
        $disable->handle($this->user($request), $request->string('password')->toString());

        return response()->json(status: 204);
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
