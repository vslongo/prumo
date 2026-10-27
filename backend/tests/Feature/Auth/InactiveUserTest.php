<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;

it('usuário desativado não acessa os módulos do core', function (string $method, string $uri) {
    $user = actingAsRole();
    $user->forceFill(['is_active' => false])->save();
    $vehicle = makeVehicle();
    $uri = str_replace(['{customer}', '{vehicle}'], [$vehicle->customer_id, $vehicle->id], $uri);

    $this->json($method, $uri, [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Teste',
        'plate' => 'ZZZ9Z99',
    ])->assertForbidden();
})->with([
    'listar clientes' => ['GET', '/api/customers'],
    'criar cliente' => ['POST', '/api/customers'],
    'ver veículo' => ['GET', '/api/vehicles/{vehicle}'],
    'criar veículo' => ['POST', '/api/vehicles'],
    'listar orçamentos' => ['GET', '/api/quotes'],
    'criar orçamento' => ['POST', '/api/quotes'],
    'listar OS' => ['GET', '/api/service-orders'],
    'abrir OS' => ['POST', '/api/service-orders'],
    'listar tipos de serviço' => ['GET', '/api/service-types'],
]);

it('usuário desativado não opera OS existente', function () {
    $user = actingAsRole();
    $vehicle = makeVehicle();
    $orderId = $this->postJson('/api/service-orders', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id])->json('data.id');
    User::query()->whereKey($user->id)->update(['is_active' => false]);
    $user->refresh();

    $this->postJson("/api/service-orders/{$orderId}/transition", ['status' => 'in_progress'])->assertForbidden();
    expect(TenantContext::id())->toBe($user->tenant_id);
});
