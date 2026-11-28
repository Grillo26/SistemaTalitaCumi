<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-002 / 07.05/RF-007 / 07.05/RN-009.
 *
 * El diagnóstico no admite coordinación: está retirado o cancelado.
 */
final class DiagnosticoNoCoordinableException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly string $estadoAtencion,
        public readonly string $estadoGeneral,
    ) {
        parent::__construct('El diagnóstico no está activo para coordinar turnos.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'DIAGNOSTICO_NO_COORDINABLE';
    }

    public function context(): array
    {
        return [
            'estado' => $this->estadoAtencion,
            'estado_general' => $this->estadoGeneral,
        ];
    }
}
