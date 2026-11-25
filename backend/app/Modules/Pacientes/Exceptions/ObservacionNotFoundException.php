<?php

namespace App\Modules\Pacientes\Exceptions;

final class ObservacionNotFoundException extends PacientesDomainException
{
    public function __construct(int $observacionId)
    {
        parent::__construct("No se encontró la observación solicitada (ID {$observacionId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'OBSERVACION_NOT_FOUND';
    }
}
