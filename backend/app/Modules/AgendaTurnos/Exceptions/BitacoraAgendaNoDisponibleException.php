<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use Throwable;

final class BitacoraAgendaNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct(public readonly ?Throwable $anterior = null)
    {
        parent::__construct(
            'No se pudo registrar la operación en la bitácora. La operación fue revertida.',
            0,
            $anterior,
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'BITACORA_AGENDA_NO_DISPONIBLE';
    }
}
