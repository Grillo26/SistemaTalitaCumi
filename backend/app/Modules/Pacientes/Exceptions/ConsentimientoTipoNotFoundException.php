<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTipoNotFoundException extends PacientesDomainException
{
    public function __construct(int $tipoId)
    {
        parent::__construct("No se encontró el tipo de consentimiento solicitado (ID {$tipoId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TYPE_NOT_FOUND';
    }
}
