<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarifa>
 */
class TarifaFactory extends Factory
{
    protected $model = Tarifa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'servicio_id' => Servicio::factory(),
            'categoria_tarifa_id' => CategoriaTarifa::factory(),
            'sucursal_id' => null,
            'monto' => '100.00',
            'vigente_desde' => now()->toDateString(),
            'vigente_hasta' => null,
            'activo' => true,
            'version' => 1,
        ];
    }
}
