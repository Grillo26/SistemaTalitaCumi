<?php

namespace App\Modules\Pacientes\Exceptions;

final class ConsentimientoTipoDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Los tipos de consentimiento no se eliminan. Desactivalos para conservar el historial.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TYPE_DELETE_NOT_ALLOWED';
    }
}
