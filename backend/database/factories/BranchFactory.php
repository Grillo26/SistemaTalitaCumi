<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('SUC-###')),
            'nombre' => fake()->unique()->company(),
            'direccion' => fake()->address(),
            'telefono' => fake()->numerify('7#######'),
            'email' => fake()->unique()->safeEmail(),
            'activo' => true,
            'version' => 1,
        ];
    }
}
