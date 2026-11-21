<?php

namespace Database\Factories;

use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Parentesco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactoEmergencia>
 */
class ContactoEmergenciaFactory extends Factory
{
    protected $model = ContactoEmergencia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'nombre_completo' => fake()->name(),
            'parentesco_id' => Parentesco::factory(),
            'telefono' => fake()->numerify('7#######'),
            'version' => 1,
        ];
    }
}
