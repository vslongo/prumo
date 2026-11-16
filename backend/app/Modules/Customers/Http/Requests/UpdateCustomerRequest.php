<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCustomerRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^[\d\s()+-]{10,20}$/'],
            'document' => ['sometimes', 'nullable', 'string', 'regex:/^[\d.\/-]{11,18}$/'],
            'consent_whatsapp' => ['sometimes', 'boolean'],
            'consent_source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
