<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FichaIngreso>
 */
class FichaIngresoFactory extends Factory
{
    protected $model = FichaIngreso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'sucursal_ingreso_id' => Branch::factory(),
            'tutor_principal_id' => null,
            'motivo_consulta_inicial' => 'Consulta inicial de ingreso.',
            'fecha_ingreso' => now()->toDateString(),
            'composicion_familiar' => null,
            'antecedentes_familiares' => null,
            'observaciones' => null,
            'derivado_por' => null,
            'derivado_por_institucion' => null,
            'derivado_por_contacto' => null,
            'estado' => EstadoFichaIngreso::BORRADOR->value,
            'estado_anterior' => null,
            'version' => 1,
            'usuario_registro_id' => User::factory(),
            'completada_en' => null,
            'completada_por_id' => null,
            'archivada_en' => null,
            'archivada_por_id' => null,
            'motivo_archivado' => null,
        ];
    }
}
