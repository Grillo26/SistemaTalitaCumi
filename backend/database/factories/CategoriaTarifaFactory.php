<?php

namespace Database\Factories;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaTarifa>
 */
class CategoriaTarifaFactory extends Factory
{
    protected $model = CategoriaTarifa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => null,
            'activo' => true,
            'version' => 1,
        ];
    }
}
