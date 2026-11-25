<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoAlreadyExistsException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El paciente ya tiene una ficha de ingreso abierta. Archivá la anterior antes de crear otra.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FICHA_ALREADY_EXISTS';
    }
}
