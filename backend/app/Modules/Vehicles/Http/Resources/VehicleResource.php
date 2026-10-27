<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Resources;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
final class VehicleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'plate' => $this->plate_normalized,
            'vin' => $this->vin,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
        ];
    }
}
