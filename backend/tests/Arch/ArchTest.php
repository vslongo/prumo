<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Shared\Traits\BelongsToTenant;

$modules = array_map('basename', glob(__DIR__.'/../../app/Modules/*', GLOB_ONLYDIR) ?: []);

arch('todo código do app usa strict_types')
    ->expect('App')
    ->toUseStrictTypes();

arch('actions são finais')
    ->expect(array_map(fn ($m) => "App\\Modules\\{$m}\\Actions", $modules))
    ->toBeFinal();

arch('models de tenant usam BelongsToTenant (exceções: Tenant e User, ADR 0001)')
    ->expect(array_map(fn ($m) => "App\\Modules\\{$m}\\Models", $modules))
    ->toUseTrait(BelongsToTenant::class)
    ->ignoring([Tenant::class, User::class]);

foreach ($modules as $module) {
    $others = array_map(fn ($m) => "App\\Modules\\{$m}\\Models", array_diff($modules, [$module]));

    arch("controllers de {$module} não usam models de outros módulos")
        ->expect("App\\Modules\\{$module}\\Http\\Controllers")
        ->not->toUse($others);
}

it('nada usa float (dinheiro é sempre centavos inteiros)', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../app'));
    $offenders = [];

    foreach ($files as $file) {
        if ($file->getExtension() === 'php' && preg_match('/\b(float|floatval|double)\b/i', (string) file_get_contents($file->getPathname()))) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});
