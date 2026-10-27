<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Actions;

use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Database\Eloquent\Collection;

/** Tipos da oficina + padrões do sistema. */
final class ListServiceTypes
{
    /** @return Collection<int, ServiceType> */
    public function handle(): Collection
    {
        return ServiceType::query()->orderBy('name')->get();
    }
}
