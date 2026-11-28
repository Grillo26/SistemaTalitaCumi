<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-002.
 *
 * Un profesional solo puede tener un turno vigente por diagnóstico. Si el
 * turno anterior fue cancelado o reprogramado, se puede programar otro.
 */
final class TurnoDiagnosticoDuplicadoException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly int $profesionalId,
        public readonly int $turnoId,
    ) {
        parent::__construct('El profesional ya tiene un turno vigente en este diagnóstico.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TURNO_DIAGNOSTICO_DUPLICADO';
    }

    public function context(): array
    {
        return [
            'profesional_id' => $this->profesionalId,
            'turno_id' => $this->turnoId,
        ];
    }
}
