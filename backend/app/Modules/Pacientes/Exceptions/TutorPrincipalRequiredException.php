<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorPrincipalRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('No se puede dejar sin tutor principal a un paciente activo. Designá otro tutor principal primero.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PRINCIPAL_REQUIRED';
    }
}
