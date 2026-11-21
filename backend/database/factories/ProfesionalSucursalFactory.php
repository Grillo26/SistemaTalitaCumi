<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfesionalSucursal>
 */
class ProfesionalSucursalFactory extends Factory
{
    protected $model = ProfesionalSucursal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profesional_id' => Profesional::factory(),
            'sucursal_id' => Branch::factory(),
            'activo' => true,
            'motivo' => null,
            'version' => 1,
        ];
    }
}
