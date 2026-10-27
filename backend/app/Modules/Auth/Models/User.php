<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Exceção documentada ao BelongsToTenant (ADR 0001): o login busca o usuário antes de existir tenant
 * no contexto. Qualquer listagem de usuários deve filtrar por `tenant_id` explicitamente.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property bool $is_active
 */
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
