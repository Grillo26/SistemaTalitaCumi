<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorPrincipalActivePatientException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor es principal de al menos un paciente activo. Designá otro tutor principal antes de desactivarlo.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PRINCIPAL_ACTIVE_PATIENT';
    }
}
