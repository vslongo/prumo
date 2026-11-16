<?php

declare(strict_types=1);

use App\Modules\Media\Jobs\PurgeStalePendingMediaJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

/*
 * Cópia de segurança do banco: pg_dump em formato próprio (já comprimido; restaura com pg_restore),
 * enviada para o bucket privado do R2 em `backups/`. Guarda os últimos 14 dias.
 * Precisa do `pg_dump` instalado no servidor (o Forge instala junto com o Postgres).
 * ponytail: mesmo bucket das fotos; bucket separado com trava de exclusão quando houver cliente pagante.
 */
Artisan::command('backup:database', function () {
    /** @var Command $this */
    $db = config('database.connections.'.config('database.default'));
    $tmp = (string) tempnam(sys_get_temp_dir(), 'prumo-backup-');

    try {
        $dump = Process::timeout(1800)
            ->env(['PGPASSWORD' => (string) $db['password']])
            ->run(['pg_dump', '--format=custom', '--no-owner', '--host', (string) $db['host'], '--port', (string) $db['port'], '--username', (string) $db['username'], '--file', $tmp, (string) $db['database']]);

        if ($dump->failed() || filesize($tmp) === 0) {
            // Sem a saída do pg_dump na mensagem: ela vai para o Sentry.
            throw new RuntimeException('Backup do banco falhou (pg_dump código '.$dump->exitCode().').');
        }

        $disk = Storage::disk('r2');
        $name = 'backups/'.now()->format('Y-m-d_His').'.dump';
        $disk->writeStream($name, fopen($tmp, 'r'));

        // O nome começa pela data, então a ordem alfabética é a ordem do tempo.
        $limit = 'backups/'.now()->subDays(14)->format('Y-m-d_His').'.dump';
        foreach ($disk->files('backups') as $old) {
            if ($old < $limit) {
                $disk->delete($old);
            }
        }

        $this->info("Backup salvo em {$name}.");
    } finally {
        @unlink($tmp);
    }
})->purpose('Copia o banco para o R2 e apaga cópias com mais de 14 dias');

Schedule::command('backup:database')->dailyAt('03:00');
Schedule::job(new PurgeStalePendingMediaJob)->dailyAt('04:00');
Schedule::command('horizon:snapshot')->everyFiveMinutes();
