<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Auth\Policies\UserPolicy;
use Illuminate\Support\Collection;

/**
 * Convites da oficina atual ainda não usados e dentro do prazo.
 * Convite de admin só aparece para o dono: o link dá o papel a quem abrir, então ver o link = poder criar admin.
 */
final class ListPendingInvitations
{
    public function __construct(private readonly UserPolicy $policy) {}

    /** @return Collection<int, UserInvitation> */
    public function handle(User $viewer): Collection
    {
        return UserInvitation::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->filter(fn (UserInvitation $i) => $this->policy->assignRole($viewer, $i->role))
            ->values()
            ->toBase();
    }
}
