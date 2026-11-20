<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\TipoSala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoSala>
 */
class TipoSalaFactory extends Factory
{
    protected $model = TipoSala::class;

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
