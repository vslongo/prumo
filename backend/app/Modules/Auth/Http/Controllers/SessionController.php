<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\VerifyTwoFactorChallenge;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\TwoFactorChallengeRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login de SPA via cookie (Sanctum). Com 2FA ativo, a senha certa não abre sessão: guarda o usuário pendente
 * na sessão por alguns minutos e devolve `two_factor: true`; o login termina em POST /login/two-factor.
 */
final class SessionController
{
    private const PENDING = 'login.two_factor';

    private const PENDING_MINUTES = 5;

    public function store(LoginRequest $request): UserResource|JsonResponse
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        if (! $guard->validate([...$request->validated(), 'is_active' => true])) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        /** @var User $user */
        $user = $guard->getLastAttempted();

        if ($user->two_factor_confirmed_at !== null) {
            $request->session()->put(self::PENDING, ['user_id' => $user->id, 'expires_at' => now()->addMinutes(self::PENDING_MINUTES)->getTimestamp()]);

            return response()->json(['two_factor' => true]);
        }

        return $this->logIn($request, $user);
    }

    public function challenge(TwoFactorChallengeRequest $request, VerifyTwoFactorChallenge $verify): UserResource
    {
        /** @var array{user_id: string, expires_at: int}|null $pending */
        $pending = $request->session()->get(self::PENDING);
        $user = $pending !== null && $pending['expires_at'] > now()->getTimestamp()
            ? User::query()->where('is_active', true)->find($pending['user_id'])
            : null;

        if ($user === null) {
            $request->session()->forget(self::PENDING);
            throw ValidationException::withMessages(['code' => 'O tempo para digitar o código acabou. Entre com a senha de novo.']);
        }

        if (! $verify->handle($user, $request->input('code'), $request->input('recovery_code'))) {
            throw ValidationException::withMessages(['code' => 'Código inválido.']);
        }

        $request->session()->forget(self::PENDING);

        return $this->logIn($request, $user);
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

    private function logIn(Request $request, User $user): UserResource
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new UserResource($user->load('tenant'));
    }
}
