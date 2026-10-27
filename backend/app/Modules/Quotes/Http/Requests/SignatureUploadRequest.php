<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SignatureUploadRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['size' => ['required', 'integer', 'min:1']];
    }
}
