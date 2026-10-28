<?php

declare(strict_types=1);

use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Modules\Messaging\Models\Message;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Str;

it('mensagens de outra oficina não aparecem no tenant atual', function () {
    actingAsRole();
    inOtherTenant(fn () => Message::create([
        'template' => MessageTemplate::QuoteLink, 'status' => MessageStatus::Queued, 'to' => '5511999999999',
        'params' => ['x'], 'sender_phone_number_id' => 'P', 'subject_type' => 'quote', 'subject_id' => (string) Str::uuid(),
        'idempotency_key' => 'k-'.TenantContext::id(),
    ]));

    expect(Message::count())->toBe(0);
});
