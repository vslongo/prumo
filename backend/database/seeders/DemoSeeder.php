<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Customers\Actions\CreateCustomer;
use App\Modules\Customers\Data\CustomerData;
use App\Modules\Tenancy\Enums\TenantStatus;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use App\Modules\Vehicles\Actions\CreateVehicle;
use App\Modules\Vehicles\Data\VehicleData;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Oficina de demonstração para dev e testes de tela (Playwright). Nunca rodar em produção.
 * Acessos: dono@demo.test e tecnico@demo.test, senha "password".
 */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder não roda em produção.');
        }

        $this->call(DatabaseSeeder::class);

        $tenant = Tenant::query()->where('name', 'Oficina Demo')->first() ?? Tenant::factory()->create([
            'name' => 'Oficina Demo',
            'status' => TenantStatus::Active,
        ]);

        foreach ([['dono@demo.test', 'Carlos Mendes', UserRole::Owner], ['tecnico@demo.test', 'Rafael Souza', UserRole::Technician]] as [$email, $name, $role]) {
            User::query()->where('email', $email)->exists() || User::factory()->create([
                'tenant_id' => $tenant->id, 'email' => $email, 'name' => $name, 'role' => $role,
            ]);
        }

        TenantContext::run($tenant->id, function () {
            if (Vehicle::query()->exists()) {
                return;
            }
            $customer = app(CreateCustomer::class)->handle(new CustomerData(
                name: 'Ana Paula Ribeiro', phone: '11987654321', consentWhatsapp: true, consentSource: 'balcao',
            ));
            app(CreateVehicle::class)->handle(new VehicleData($customer->id, 'BRA2E19', null, 'Volkswagen', 'Gol 1.6', 2018));
            app(CreateVehicle::class)->handle(new VehicleData($customer->id, 'QWE1234', null, 'Fiat', 'Strada', 2021));
        });
    }
}
