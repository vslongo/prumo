<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use App\Modules\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
final class CustomerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document' => $this->document,
            'consent_whatsapp_at' => $this->consent_whatsapp_at?->toIso8601String(),
            'consent_whatsapp_source' => $this->consent_whatsapp_source,
            'anonymized_at' => $this->anonymized_at?->toIso8601String(),
        ];
    }
}
