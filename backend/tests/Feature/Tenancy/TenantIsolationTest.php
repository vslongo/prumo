<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\Tenancy\Exceptions\MissingTenantContext;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;

it('não devolve nada sem tenant no contexto (falha fechada)', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::run($tenant->id, fn () => Customer::factory()->create());

    expect(Customer::count())->toBe(0)
        ->and(TenantContext::run($tenant->id, fn () => Customer::count()))->toBe(1);
});

it('preenche tenant_id pelo contexto e recusa criar sem contexto', function () {
    $tenant = Tenant::factory()->create();

    $customer = TenantContext::run($tenant->id, fn () => Customer::create(['name' => 'Ana']));

    expect($customer->tenant_id)->toBe($tenant->id)
        ->and(fn () => Customer::create(['name' => 'Sem tenant']))->toThrow(MissingTenantContext::class);
});

it('recusa tenant_id diferente do contexto', function () {
    [$a, $b] = Tenant::factory()->count(2)->create();

    TenantContext::run($a->id, function () use ($b) {
        $customer = new Customer(['name' => 'Ana']);
        $customer->tenant_id = $b->id;
        $customer->save();
    });
})->throws(LogicException::class);

it('tipos de serviço padrão aparecem para todos; os da oficina só para ela', function () {
    [$a, $b] = Tenant::factory()->count(2)->create();
    ServiceType::factory()->create(['name' => 'Padrão']);
    TenantContext::run($a->id, fn () => ServiceType::factory()->create(['name' => 'Da A']));

    $seenByB = TenantContext::run($b->id, fn () => ServiceType::pluck('name')->all());

    expect($seenByB)->toBe(['Padrão']);
});
