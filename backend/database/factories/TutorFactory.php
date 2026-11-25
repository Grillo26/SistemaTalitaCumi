<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tutor>
 */
class TutorFactory extends Factory
{
    protected $model = Tutor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_registro_id' => Branch::factory(),
            'nombre_completo' => fake()->name(),
            'ci' => (string) fake()->unique()->numerify('########'),
            'telefono' => fake()->numerify('7#######'),
            'telefono_alternativo' => null,
            'email' => null,
            'direccion' => null,
            'parentesco' => 'Madre',
            'parentesco_id' => Parentesco::factory(),
            'activo' => true,
            'version' => 1,
        ];
    }
}
