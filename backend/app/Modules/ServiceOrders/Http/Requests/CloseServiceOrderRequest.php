<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CloseServiceOrderRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['mileage_out' => ['required', 'integer', 'min:0', 'max:9999999']];
    }
}
