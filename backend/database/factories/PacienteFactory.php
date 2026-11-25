<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'PAC-'.fake()->unique()->numerify('########'),
            'nombre' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-12 years', '-2 years')->format('Y-m-d'),
            'sexo' => fake()->randomElement(['M', 'F']),
            'ci' => (string) fake()->unique()->numerify('########'),
            'tipo_diagnostico_id' => null,
            'sucursal_registro_id' => Branch::factory(),
            'estado' => 'activo',
            'alergias' => null,
            'condiciones_medicas' => null,
            'observaciones' => null,
            'version' => 1,
        ];
    }
}
