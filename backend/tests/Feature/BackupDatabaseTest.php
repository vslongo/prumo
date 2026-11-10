<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/** Faz o papel do pg_dump: escreve no arquivo passado em `--file`. */
function fakePgDump(string $content, int $exitCode = 0): void
{
    Process::fake(function (PendingProcess $process) use ($content, $exitCode) {
        /** @var list<string> $command */
        $command = $process->command;
        file_put_contents($command[(int) array_search('--file', $command, true) + 1], $content);

        return Process::result(exitCode: $exitCode);
    });
}

it('manda o dump para o R2 e apaga as cópias com mais de 14 dias', function () {
    Storage::fake('r2');
    $this->travelTo('2026-11-09 03:00:00');
    Storage::disk('r2')->put('backups/2026-10-20_030000.dump', 'velho');
    Storage::disk('r2')->put('backups/2026-11-01_030000.dump', 'recente');
    fakePgDump('conteudo-do-dump');

    $this->artisan('backup:database')->assertSuccessful();

    expect(Storage::disk('r2')->get('backups/2026-11-09_030000.dump'))->toBe('conteudo-do-dump');
    Storage::disk('r2')->assertMissing('backups/2026-10-20_030000.dump');
    Storage::disk('r2')->assertExists('backups/2026-11-01_030000.dump');
    Process::assertRan(fn (PendingProcess $p) => is_array($p->command) && $p->command[0] === 'pg_dump' && ! in_array($p->environment['PGPASSWORD'] ?? null, $p->command, true));
});

it('falha sem salvar nada quando o pg_dump falha ou sai vazio', function (string $content, int $exitCode) {
    Storage::fake('r2');
    fakePgDump($content, $exitCode);

    expect(fn () => $this->artisan('backup:database')->run())->toThrow(RuntimeException::class);
    expect(Storage::disk('r2')->files('backups'))->toBe([]);
})->with([
    'erro do pg_dump' => ['lixo', 1],
    'dump vazio' => ['', 0],
]);
