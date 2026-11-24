<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use Illuminate\Support\Facades\Process;

/**
 * Abre um .pfx pelo binário `openssl`, com tempo máximo: a contagem de iterações vem do próprio arquivo
 * (arquivo montado = CPU presa). Senha por variável de ambiente, nunca na linha de comando.
 * Tenta de novo com `-legacy`: muito A1 brasileiro ainda vem cifrado com algoritmo antigo (RC2/3DES).
 */
final class Pkcs12
{
    private const TIMEOUT_SECONDS = 10;

    /** PEM do certificado (e da chave privada, se `$withKey`) ou null se não abriu. */
    public static function toPem(#[\SensitiveParameter] string $pfx, #[\SensitiveParameter] string $password, bool $withKey = false): ?string
    {
        $args = $withKey ? ['-nodes'] : ['-clcerts', '-nokeys'];

        foreach ([[], ['-legacy']] as $extra) {
            $result = Process::timeout(self::TIMEOUT_SECONDS)
                ->env(['PFX_PASS' => $password])
                ->input($pfx)
                ->run(['openssl', 'pkcs12', ...$args, '-passin', 'env:PFX_PASS', ...$extra]);

            $pem = $result->output();
            if ($result->successful() && str_contains($pem, 'BEGIN CERTIFICATE') && (! $withKey || str_contains($pem, 'PRIVATE KEY'))) {
                return $pem;
            }
        }

        return null;
    }
}
