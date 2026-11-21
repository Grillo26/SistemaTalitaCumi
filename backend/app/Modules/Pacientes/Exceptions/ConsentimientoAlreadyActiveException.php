<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoAlreadyActiveException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un consentimiento vigente de este tipo para el tutor seleccionado.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_ALREADY_ACTIVE';
    }
}
