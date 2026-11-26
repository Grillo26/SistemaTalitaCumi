<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class CatalogoAreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => null,
            'color' => '#671C61',
            'activo' => true,
            'version' => 1,
        ];
    }
}
