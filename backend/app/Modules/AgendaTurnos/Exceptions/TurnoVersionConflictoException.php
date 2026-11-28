<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RT-001 — la versión enviada no coincide con la actual.
 */
final class TurnoVersionConflictoException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly EstadoTurno $estadoActual,
        public readonly int $versionActual,
    ) {
        parent::__construct(
            'El turno cambió desde que lo cargaste. Recargá la ficha antes de continuar.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TURNO_VERSION_CONFLICTO';
    }

    public function context(): array
    {
        return [
            'estado_actual' => $this->estadoActual->value,
            'estado_actual_label' => $this->estadoActual->label(),
            'version_actual' => $this->versionActual,
        ];
    }
}
