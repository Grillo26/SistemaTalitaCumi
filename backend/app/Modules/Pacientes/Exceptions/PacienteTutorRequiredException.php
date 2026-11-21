<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteTutorRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Todo paciente activo debe tener al menos un tutor principal asociado.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_TUTOR_REQUIRED';
    }
}
