<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Exceptions;

use RuntimeException;

final class MissingTenantContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Nenhum tenant no contexto atual.');
    }
}
