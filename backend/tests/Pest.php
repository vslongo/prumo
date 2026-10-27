<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Customers\Models\Customer;
use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/** Cria tenant + usuário, autentica e coloca o tenant no contexto (para factories). */
function actingAsRole(UserRole $role = UserRole::Owner, ?Tenant $tenant = null): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->for($tenant)->role($role)->create();
    Sanctum::actingAs($user);
    TenantContext::set($tenant->id);

    return $user;
}

/** Cria dados em outro tenant sem trocar o tenant do teste. */
function inOtherTenant(Closure $callback, ?Tenant $tenant = null): mixed
{
    $tenant ??= Tenant::factory()->create();

    return TenantContext::run($tenant->id, $callback);
}

function makeVehicle(): Vehicle
{
    return Vehicle::factory()->create();
}

function oilChangeType(): ServiceType
{
    return ServiceType::factory()->create();
}

function oilItem(User $technician, ServiceType $type, array $overrides = []): array
{
    return [
        'kind' => 'service',
        'description' => 'Troca de óleo',
        'service_type_id' => $type->id,
        'technician_id' => $technician->id,
        'quantity' => '1',
        'unit_price_cents' => 8000,
        'fields' => ['oil_brand' => 'Mobil', 'viscosity' => '5W30', 'liters' => 4, 'filter_changed' => true],
        ...$overrides,
    ];
}

function productItem(User $technician, array $overrides = []): array
{
    return [
        'kind' => 'product',
        'description' => 'Óleo 5W30 (litro)',
        'technician_id' => $technician->id,
        'quantity' => '4.5',
        'unit_price_cents' => 3999,
        ...$overrides,
    ];
}

function customerOf(Vehicle $vehicle): Customer
{
    return $vehicle->customer;
}
