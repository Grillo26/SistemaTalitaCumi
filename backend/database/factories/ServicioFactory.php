<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'tipo_servicio_id' => TipoServicio::factory(),
            'nombre' => ucfirst(fake()->unique()->words(3, true)),
            'descripcion' => null,
            'duracion_minutos' => 50,
            'activo' => true,
            'version' => 1,
        ];
    }
}
