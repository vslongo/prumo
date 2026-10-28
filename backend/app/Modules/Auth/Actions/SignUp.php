<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Data\SignUpData;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\Actions\CreateTenant;
use Illuminate\Support\Facades\DB;

/** Autoatendimento: cria a oficina (em trial) e o usuário dono dela. */
final class SignUp
{
    public function __construct(private readonly CreateTenant $createTenant) {}

    public function handle(SignUpData $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = $this->createTenant->handle($data->workshopName);

            $user = new User(['name' => $data->name, 'email' => $data->email, 'password' => $data->password]);
            $user->tenant_id = $tenant->id;
            $user->role = UserRole::Owner;
            $user->save();

            return $user;
        });
    }
}
