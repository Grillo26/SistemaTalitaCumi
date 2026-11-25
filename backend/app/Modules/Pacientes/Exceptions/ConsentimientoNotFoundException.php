<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoNotFoundException extends PacientesDomainException
{
    public function __construct(int $consentimientoId)
    {
        parent::__construct("No se encontró el consentimiento solicitado (ID {$consentimientoId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'CONSENT_NOT_FOUND';
    }
}
