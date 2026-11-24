<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

/** Tomador do serviço (cliente da OS). Dado pessoal: não logar. */
final readonly class Taker
{
    public function __construct(
        #[\SensitiveParameter] public string $document,
        public string $name,
        public ?string $email,
    ) {}
}
