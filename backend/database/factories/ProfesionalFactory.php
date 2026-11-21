<?php

namespace Database\Factories;

use App\Modules\Profesionales\Models\Profesional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesional>
 */
class ProfesionalFactory extends Factory
{
    protected $model = Profesional::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_completo' => fake()->name(),
            'ci' => (string) fake()->unique()->numerify('########'),
            'telefono' => fake()->numerify('7#######'),
            'telefono_2' => null,
            'email' => fake()->unique()->safeEmail(),
            'matricula' => null,
            'activo' => true,
            'version' => 1,
        ];
    }
}
