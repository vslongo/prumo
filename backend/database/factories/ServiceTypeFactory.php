<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceType> */
final class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        return [
            'name' => 'Troca de óleo',
            'default_price_cents' => 8000,
            'fields_schema' => [
                ['key' => 'oil_brand', 'label' => 'Marca do óleo', 'type' => 'text', 'required' => true],
                ['key' => 'viscosity', 'label' => 'Viscosidade', 'type' => 'select', 'required' => true, 'options' => ['5W30', '10W40', '15W40']],
                ['key' => 'liters', 'label' => 'Litros', 'type' => 'number', 'required' => true, 'unit' => 'L'],
                ['key' => 'filter_changed', 'label' => 'Trocou filtro', 'type' => 'boolean', 'required' => false],
                ['key' => 'next_change_km', 'label' => 'Próxima troca (km)', 'type' => 'number', 'required' => false, 'unit' => 'km'],
            ],
        ];
    }
}
