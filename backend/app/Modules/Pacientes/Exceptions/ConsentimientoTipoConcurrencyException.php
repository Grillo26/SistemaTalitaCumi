<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTipoConcurrencyException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tipo de consentimiento fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TYPE_VERSION_CONFLICT';
    }
}
