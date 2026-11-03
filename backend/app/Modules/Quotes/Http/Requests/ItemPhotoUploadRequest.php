<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Requests;

use App\Modules\Media\Actions\RequestUploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ItemPhotoUploadRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mime' => ['required', 'string', Rule::in(RequestUploadUrl::PHOTO_MIMES)],
            'size' => ['required', 'integer', 'min:1', 'max:'.RequestUploadUrl::MAX_BYTES],
        ];
    }
}
