<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoDiagnostico>
 */
class TipoDiagnosticoFactory extends Factory
{
    protected $model = TipoDiagnostico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'servicio_id' => Servicio::factory(),
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => null,
            'dias_estimados' => 1,
            'activo' => true,
            'version' => 1,
        ];
    }
}
