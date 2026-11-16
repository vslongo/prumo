<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TwoFactorChallengeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
