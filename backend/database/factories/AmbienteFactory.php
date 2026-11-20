<?php

namespace Database\Factories;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ambiente>
 */
class AmbienteFactory extends Factory
{
    protected $model = Ambiente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Branch::factory(),
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'uso' => fake()->randomElement(UsoAmbiente::values()),
            'observaciones' => null,
            'activo' => true,
            'version' => 1,
        ];
    }
}
