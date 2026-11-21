<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Los consentimientos no se eliminan. Registrá una revocación para conservar el historial.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_DELETE_NOT_ALLOWED';
    }
}
