<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use RuntimeException;

/** Certificado ausente, vencido, trocado ou ilegível: a nota vai para `error` com a mensagem (em português). */
final class InvalidCertificate extends RuntimeException {}
