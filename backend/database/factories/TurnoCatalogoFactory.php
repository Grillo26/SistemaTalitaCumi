<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TurnoCatalogo>
 */
class TurnoCatalogoFactory extends Factory
{
    protected $model = TurnoCatalogo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'orden' => fake()->numberBetween(1, 99),
            'activo' => true,
            'version' => 1,
        ];
    }
}
