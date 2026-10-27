<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Requests;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListServiceOrdersRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ServiceOrderStatus::class)],
            'vehicle_id' => ['nullable', 'uuid'],
            'quote_id' => ['nullable', 'uuid'],
        ];
    }
}
