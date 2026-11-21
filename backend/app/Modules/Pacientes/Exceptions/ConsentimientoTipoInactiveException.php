<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTipoInactiveException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tipo de consentimiento está inactivo y no admite nuevos registros.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TYPE_INACTIVE';
    }
}
