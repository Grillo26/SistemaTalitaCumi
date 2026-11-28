<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RT-001 — ya existe una asistencia registrada en otro estado.
 *
 * El cliente debe recargar y usar la corrección, no el registro inicial.
 */
final class AsistenciaYaRegistradaException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly EstadoTurno $estadoActual,
        public readonly int $version,
    ) {
        parent::__construct(
            "El turno ya tiene asistencia registrada como «{$estadoActual->label()}». Recargá la ficha para corregirla.",
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ASISTENCIA_YA_REGISTRADA';
    }

    public function context(): array
    {
        return [
            'estado_actual' => $this->estadoActual->value,
            'estado_actual_label' => $this->estadoActual->label(),
            'version' => $this->version,
        ];
    }
}
