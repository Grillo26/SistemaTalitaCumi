<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTutorRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El paciente no tiene tutores activos asociados. Agregá un tutor antes de registrar consentimientos.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TUTOR_REQUIRED';
    }
}
