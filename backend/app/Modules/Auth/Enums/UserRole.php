<?php

declare(strict_types=1);

namespace App\Modules\Auth\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Technician = 'technician';
    case Front = 'front';

    /** Configurações da oficina (tipos de serviço, usuários). Técnico e balcão não acessam. */
    public function managesSettings(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
