<?php

declare(strict_types=1);

// Cada módulo declara as próprias rotas e middlewares em app/Modules/<Modulo>/routes.php.
foreach (glob(app_path('Modules/*/routes.php')) ?: [] as $routes) {
    require $routes;
}
