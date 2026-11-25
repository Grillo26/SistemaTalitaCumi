<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Enums\EstadoFichaIngreso;

/**
 * Resumen no sensible de la ficha vigente para 04.04 y consumidores.
 */
final readonly class FichaIngresoResumen
{
    public function __construct(
        public int $id,
        public int $pacienteId,
        public EstadoFichaIngreso $estado,
        public string $fechaIngreso,
        public int $sucursalIngresoId,
        public int $version,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'paciente_id' => $this->pacienteId,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'fecha_ingreso' => $this->fechaIngreso,
            'sucursal_ingreso_id' => $this->sucursalIngresoId,
            'version' => $this->version,
        ];
    }
}
