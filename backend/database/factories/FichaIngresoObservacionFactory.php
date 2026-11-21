<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FichaIngresoObservacion>
 */
class FichaIngresoObservacionFactory extends Factory
{
    protected $model = FichaIngresoObservacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ficha_ingreso_id' => FichaIngreso::factory(),
            'fecha' => now()->toDateString(),
            'texto' => 'Observación de prueba.',
            'observacion_referencia_id' => null,
            'usuario_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
