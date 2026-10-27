<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FindVehicleByPlateRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['plate' => ['required', 'string', 'max:10']];
    }
}
