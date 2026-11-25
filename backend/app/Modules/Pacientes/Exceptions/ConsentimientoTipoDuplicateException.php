<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTipoDuplicateException extends PacientesDomainException
{
    public function __construct(string $codigo)
    {
        parent::__construct("Ya existe un tipo de consentimiento con el código «{$codigo}».");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TYPE_ALREADY_EXISTS';
    }
}
