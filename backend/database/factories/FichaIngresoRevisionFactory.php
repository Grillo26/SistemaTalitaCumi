<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FichaIngresoRevision>
 */
class FichaIngresoRevisionFactory extends Factory
{
    protected $model = FichaIngresoRevision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ficha_ingreso_id' => FichaIngreso::factory(),
            'version' => 1,
            'accion' => 'CREAR',
            'snapshot' => [],
            'campos_modificados' => null,
            'usuario_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
