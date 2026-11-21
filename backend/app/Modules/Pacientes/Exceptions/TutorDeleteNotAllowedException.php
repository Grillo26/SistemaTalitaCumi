<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Los tutores no se eliminan. Desactivalos para conservar el historial y la bitácora.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_DELETE_NOT_ALLOWED';
    }
}
