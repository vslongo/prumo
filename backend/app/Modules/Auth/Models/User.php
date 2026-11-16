<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
 * @property string $password
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes hashes sha256
 * @property Carbon|null $two_factor_confirmed_at
 * @property int|null $two_factor_last_step último passo TOTP aceito (barra reuso do código)
 * @property Carbon|null $terms_accepted_at aceite dos termos no cadastro (ADR 0008)
 * @property string|null $terms_version
 * @property Carbon|null $created_at
 */
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    /** Recém-criado (cadastro, convite) já nasce sem 2FA, sem precisar reler do banco. */
    protected $attributes = ['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_step' => 'integer',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /** "Esqueci minha senha" (ADR 0007): e-mail em português, pela fila, com link para o app. */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
