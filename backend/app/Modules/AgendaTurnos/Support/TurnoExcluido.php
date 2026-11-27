<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * Turno que no entra en una cancelación masiva y el motivo.
 */
final readonly class TurnoExcluido
{
    public function __construct(
        public int $turnoId,
        public string $motivo,
        public EstadoTurno $estado,
        public string $fecha,
        public string $horaInicio,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'turno_id' => $this->turnoId,
            'motivo' => $this->motivo,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
        ];
    }
}
