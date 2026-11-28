<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RF-003, 07.04/RF-008, 07.04/RN-005.
 *
 * El turno no admite el registro o la corrección solicitada.
 */
final class TurnoNoAsistibleException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly EstadoTurno $estadoActual,
        public readonly string $detalle,
    ) {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TURNO_NO_ASISTIBLE';
    }

    public function context(): array
    {
        return [
            'estado_actual' => $this->estadoActual->value,
            'estado_actual_label' => $this->estadoActual->label(),
        ];
    }
}
