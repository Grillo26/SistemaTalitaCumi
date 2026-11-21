<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteNotFoundException extends PacientesDomainException
{
    public function __construct(int $pacienteId)
    {
        parent::__construct("No se encontró el paciente solicitado (ID {$pacienteId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PATIENT_NOT_FOUND';
    }
}
