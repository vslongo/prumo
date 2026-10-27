<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Requests;

use App\Modules\Quotes\Enums\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListQuotesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(QuoteStatus::class)],
            'vehicle_id' => ['nullable', 'uuid'],
        ];
    }
}
