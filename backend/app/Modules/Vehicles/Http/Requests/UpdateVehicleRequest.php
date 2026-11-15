<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateVehicleRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = (new StoreVehicleRequest)->rules();
        unset($rules['customer_id']);

        return $rules;
    }
}
