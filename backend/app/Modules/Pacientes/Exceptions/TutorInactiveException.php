<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorInactiveException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor está inactivo y no puede asociarse a un paciente.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_INACTIVE';
    }
}
