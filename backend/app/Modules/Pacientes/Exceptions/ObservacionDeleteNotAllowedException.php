<?php

namespace App\Modules\Pacientes\Exceptions;

final class ObservacionDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Las observaciones no se eliminan. Agregá una corrección que referencie la observación anterior.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'OBSERVACION_DELETE_NOT_ALLOWED';
    }
}
