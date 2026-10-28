<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Data;

use App\Modules\Messaging\Enums\MessageTemplate;

final readonly class OutboundMessageData
{
    /** @param list<string> $params parâmetros do corpo do template, na ordem */
    public function __construct(
        public string $tenantId,
        public MessageTemplate $template,
        public string $to,
        public array $params,
        public string $subjectType,
        public string $subjectId,
        public string $idempotencyKey,
        public ?string $customerId = null,
    ) {}
}
