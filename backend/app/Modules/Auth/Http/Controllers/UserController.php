<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\ListTenantUsers;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Lista para escolher o técnico responsável de cada item. Qualquer papel ativo da oficina pode ver. */
final class UserController
{
    public function index(ListTenantUsers $list): AnonymousResourceCollection
    {
        return UserResource::collection($list->handle());
    }
}
