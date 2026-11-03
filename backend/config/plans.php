<?php

declare(strict_types=1);

// Planos do SaaS (CLAUDE.md §1). Preço em centavos; max_users null = sem limite.
// Durante o trial não há limite de usuários.
return [
    'trial_days' => 14,

    // past_due por mais que isso → somente leitura.
    'past_due_grace_days' => 7,

    'plans' => [
        'solo' => ['name' => 'Solo', 'price_cents' => 4900, 'max_users' => 1],
        'oficina' => ['name' => 'Oficina', 'price_cents' => 7900, 'max_users' => 5],
        'pro' => ['name' => 'Pro', 'price_cents' => 14900, 'max_users' => null],
    ],
];
