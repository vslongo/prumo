<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Requests;

use App\Modules\Messaging\Enums\MessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DeliveryStatusRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'uuid'],
            'message_id' => ['required', 'uuid'],
            'status' => ['required', Rule::enum(MessageStatus::class)->except([MessageStatus::Queued])],
            'provider_message_id' => ['nullable', 'string', 'max:120'],
            'error' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
