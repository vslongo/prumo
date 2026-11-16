<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class SignUpRequest extends FormRequest
{
    // E-mail único global e comparado com `=` no Postgres: normaliza antes do `unique`.
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'workshop_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'accept_terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já tem cadastro. Entre com ele ou use outro.',
            'accept_terms.accepted' => 'Para criar a conta é preciso aceitar os termos de uso e a política de privacidade.',
        ];
    }
}
