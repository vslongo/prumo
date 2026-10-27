<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Database\Seeder;

/** Tipos de serviço padrão do sistema (tenant_id null). Idempotente. */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'Troca de óleo' => [8000, [
                ['key' => 'oil_brand', 'label' => 'Marca do óleo', 'type' => 'text', 'required' => true],
                ['key' => 'viscosity', 'label' => 'Viscosidade', 'type' => 'select', 'required' => true, 'options' => ['0W20', '5W30', '5W40', '10W40', '15W40', '20W50']],
                ['key' => 'liters', 'label' => 'Litros', 'type' => 'number', 'required' => true, 'unit' => 'L'],
                ['key' => 'filter_changed', 'label' => 'Trocou filtro', 'type' => 'boolean', 'required' => false],
                ['key' => 'next_change_km', 'label' => 'Próxima troca (km)', 'type' => 'number', 'required' => false, 'unit' => 'km'],
            ]],
            'Troca de junta' => [25000, [
                ['key' => 'gasket', 'label' => 'Junta', 'type' => 'select', 'required' => true, 'options' => ['cabeçote', 'tampa de válvulas', 'cárter', 'coletor']],
                ['key' => 'side', 'label' => 'Lado', 'type' => 'select', 'required' => false, 'options' => ['esquerdo', 'direito', 'ambos']],
                ['key' => 'resurfacing', 'label' => 'Retífica', 'type' => 'boolean', 'required' => false],
            ]],
            'Serviço geral' => [0, []],
        ];

        foreach ($defaults as $name => [$price, $schema]) {
            // Sem tenant no contexto, o scope só enxerga tipos padrão (tenant_id null).
            ServiceType::query()->updateOrCreate(
                ['name' => $name],
                ['fields_schema' => $schema, 'default_price_cents' => $price],
            );
        }
    }
}
