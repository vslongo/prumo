<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\ChangeUserRole;
use App\Modules\Auth\Actions\DeactivateUser;
use App\Modules\Auth\Actions\ListTenantUsers;
use App\Modules\Auth\Actions\ReactivateUser;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Http\Requests\ChangeUserRoleRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class UserController
{
    /** Equipe da oficina. Qualquer papel ativo vê (para escolher o técnico de cada item). */
    public function index(ListTenantUsers $list): AnonymousResourceCollection
    {
        return UserResource::collection($list->handle());
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user, ChangeUserRole $change): UserResource
    {
        Gate::authorize('manage', $user);

        return new UserResource($change->handle($user, UserRole::from((string) $request->validated('role'))));
    }

    public function deactivate(User $user, DeactivateUser $deactivate): UserResource
    {
        Gate::authorize('manage', $user);

        return new UserResource($deactivate->handle($user));
    }

    public function reactivate(User $user, ReactivateUser $reactivate): UserResource
    {
        Gate::authorize('manage', $user);

        return new UserResource($reactivate->handle($user));
    }
}
