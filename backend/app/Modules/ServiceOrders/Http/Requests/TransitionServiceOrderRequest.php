<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Requests;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionServiceOrderRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(ServiceOrderStatus::class)]];
    }
}
