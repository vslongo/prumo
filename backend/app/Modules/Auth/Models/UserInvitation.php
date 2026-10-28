<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Modules\Auth\Enums\UserRole;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Convite de funcionário. Não guarda e-mail nem telefone: quem aceita escolhe como vai entrar
 * (hoje e-mail + senha; login por telefone entra no aceite sem mudar o convite).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property UserRole $role
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property string $invited_by_id
 * @property string|null $accepted_user_id
 * @property Carbon|null $accepted_at
 */
final class UserInvitation extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['name', 'role', 'token_hash', 'expires_at', 'invited_by_id'];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
