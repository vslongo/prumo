<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\AcceptInvitation;
use App\Modules\Auth\Actions\InviteUser;
use App\Modules\Auth\Actions\ListPendingInvitations;
use App\Modules\Auth\Actions\RevokeInvitation;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Http\Requests\AcceptInvitationRequest;
use App\Modules\Auth\Http\Requests\InviteUserRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class InvitationController
{
    /** Convites pendentes, para o dono acompanhar e cancelar. O link não é guardado, então não volta aqui. */
    public function index(ListPendingInvitations $list): JsonResponse
    {
        Gate::authorize('viewAny', UserInvitation::class);

        return response()->json(['data' => $list->handle()->map(fn (UserInvitation $i) => [
            'id' => $i->id,
            'name' => $i->name,
            'role' => $i->role->value,
            'expires_at' => $i->expires_at->toIso8601String(),
        ])]);
    }

    public function destroy(UserInvitation $userInvitation, RevokeInvitation $revoke): JsonResponse
    {
        Gate::authorize('delete', $userInvitation);
        $revoke->handle($userInvitation);

        return response()->json(status: 204);
    }

    public function store(InviteUserRequest $request, InviteUser $invite): JsonResponse
    {
        $role = UserRole::from((string) $request->validated('role'));
        Gate::authorize('create', UserInvitation::class);
        Gate::authorize('assignRole', [User::class, $role]);

        /** @var User $user */
        $user = $request->user();
        $sent = $invite->handle($user, (string) $request->validated('name'), $role);

        return response()->json(['data' => [
            'link' => $sent['link'],
            'whatsapp_url' => $sent['whatsapp_url'],
            'expires_at' => $sent['expires_at'],
        ]], 201);
    }

    /** Público: o que a tela de aceite mostra. Sem id interno. */
    public function show(Request $request): JsonResponse
    {
        /** @var UserInvitation $invitation */
        $invitation = $request->attributes->get('invitation');

        return response()->json(['data' => [
            'name' => $invitation->name,
            'role' => $invitation->role->value,
            'workshop' => $invitation->tenant?->name,
            'usable' => $invitation->isUsable(),
        ]]);
    }

    public function accept(AcceptInvitationRequest $request, AcceptInvitation $accept): JsonResponse
    {
        /** @var UserInvitation $invitation */
        $invitation = $request->attributes->get('invitation');
        /** @var array{name: string, email: string, password: string} $data */
        $data = $request->validated();

        $user = $accept->handle($invitation, $data['name'], $data['email'], $data['password']);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new UserResource($user->load('tenant')))->response()->setStatusCode(201);
    }
}
