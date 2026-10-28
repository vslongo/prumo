<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\SignUp;
use App\Modules\Auth\Data\SignUpData;
use App\Modules\Auth\Http\Requests\SignUpRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/** Cadastro de oficina pelo site: cria tenant + dono e já deixa logado. */
final class SignUpController
{
    public function store(SignUpRequest $request, SignUp $signUp): JsonResponse
    {
        $user = $signUp->handle(SignUpData::fromArray($request->validated()));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new UserResource($user->load('tenant')))->response()->setStatusCode(201);
    }
}
