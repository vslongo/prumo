<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Requests;

use App\Modules\Billing\Enums\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangePlanRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['plan' => ['required', Rule::enum(Plan::class)]];
    }
}
