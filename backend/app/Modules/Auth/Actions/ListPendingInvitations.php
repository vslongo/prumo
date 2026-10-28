<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\UserInvitation;
use Illuminate\Database\Eloquent\Collection;

/** Convites da oficina atual ainda não usados e dentro do prazo. */
final class ListPendingInvitations
{
    /** @return Collection<int, UserInvitation> */
    public function handle(): Collection
    {
        return UserInvitation::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();
    }
}
