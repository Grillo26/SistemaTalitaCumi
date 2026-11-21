<?php

namespace Database\Factories;

use App\Modules\Pacientes\Models\Parentesco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parentesco>
 */
class ParentescoFactory extends Factory
{
    protected $model = Parentesco::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Parentesco '.fake()->unique()->numerify('####'),
            'activo' => true,
            'orden' => fake()->numberBetween(0, 100),
        ];
    }
}
