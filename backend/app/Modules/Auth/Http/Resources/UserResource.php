<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\Actions\ResolveTwoFactorRequirement;
use App\Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            // Recém-criado ainda não leu o default do banco: sem valor = ativo.
            'is_active' => $this->is_active ?? true,
            'workshop' => $this->whenLoaded('tenant', fn () => $this->tenant?->name),
            'two_factor' => $this->twoFactor(),
        ];
    }

    /** @return array{enabled: bool, required_from: string|null, blocked: bool} */
    private function twoFactor(): array
    {
        $state = app(ResolveTwoFactorRequirement::class)->handle($this->resource);

        return [...$state, 'required_from' => $state['required_from']?->toIso8601String()];
    }
}
