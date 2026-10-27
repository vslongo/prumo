<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Requests;

use App\Shared\Support\ItemRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuoteItemRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ItemRules::for();
    }
}
