<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Quotes\Models\Quote;

/** Todos os papéis (inclusive técnico) criam, editam e enviam orçamentos. */
final class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->is_active && $quote->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Quote $quote): bool
    {
        return $this->view($user, $quote);
    }
}
