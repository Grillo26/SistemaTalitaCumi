<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class AsignacionNoPermitidaException extends AgendaTurnosDomainException
{
    /**
     * @param  list<array{id: int, motivo: string}>  $turnos
     */
    public function __construct(
        public readonly array $turnos,
    ) {
        parent::__construct(
            'La asignación de profesional solo aplica a turnos futuros en estado Programado. No se modificó ningún turno.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ASIGNACION_NO_PERMITIDA';
    }

    public function context(): array
    {
        return ['turnos' => $this->turnos];
    }
}
