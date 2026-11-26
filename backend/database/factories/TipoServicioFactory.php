<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\TipoServicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoServicio>
 */
class TipoServicioFactory extends Factory
{
    protected $model = TipoServicio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'activo' => true,
            'version' => 1,
        ];
    }
}
