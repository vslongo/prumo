<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveQuoteRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signer_name' => ['required', 'string', 'min:3', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['integer', 'min:1'],
        ];
    }
}
