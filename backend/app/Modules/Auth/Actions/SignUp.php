<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Data\SignUpData;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\Actions\CreateTenant;
use Illuminate\Support\Facades\DB;

/** Autoatendimento: cria a oficina (em trial) e o usuário dono dela, com o aceite dos termos (ADR 0008). */
final class SignUp
{
    /** Data da versão publicada em /termos e /privacidade. Mudou o texto → mude aqui. */
    public const TERMS_VERSION = '2026-11-09';

    public function __construct(private readonly CreateTenant $createTenant) {}

    public function handle(SignUpData $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = $this->createTenant->handle($data->workshopName);

            $user = new User(['name' => $data->name, 'email' => $data->email, 'password' => $data->password]);
            $user->tenant_id = $tenant->id;
            $user->role = UserRole::Owner;
            // O FormRequest só deixa chegar aqui com o aceite marcado.
            $user->terms_accepted_at = now();
            $user->terms_version = self::TERMS_VERSION;
            $user->save();

            return $user;
        });
    }
}
