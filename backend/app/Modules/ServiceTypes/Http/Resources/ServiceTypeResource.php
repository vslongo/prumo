<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Http\Resources;

use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceType */
final class ServiceTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fields_schema' => $this->fields_schema,
            'default_price_cents' => $this->default_price_cents,
            'is_system_default' => $this->isSystemDefault(),
        ];
    }
}
