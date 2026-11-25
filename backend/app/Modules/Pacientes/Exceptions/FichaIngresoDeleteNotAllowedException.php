<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Las fichas de ingreso no se eliminan. Archivalas para conservar el historial y la bitácora.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FICHA_DELETE_NOT_ALLOWED';
    }
}
