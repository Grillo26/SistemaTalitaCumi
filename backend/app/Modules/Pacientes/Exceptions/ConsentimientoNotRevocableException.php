<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoNotRevocableException extends PacientesDomainException
{
    public function __construct(string $message = 'Solo se puede revocar un consentimiento otorgado y vigente.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_NOT_REVOCABLE';
    }
}
