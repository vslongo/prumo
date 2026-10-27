<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[\d\s()+-]{10,20}$/'],
            'document' => ['nullable', 'string', 'regex:/^[\d.\/-]{11,18}$/'],
            'consent_whatsapp' => ['boolean'],
            'consent_source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
