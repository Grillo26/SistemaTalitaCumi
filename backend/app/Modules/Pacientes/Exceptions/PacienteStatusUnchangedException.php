<?php

namespace App\Modules\Pacientes\Exceptions;

use App\Modules\Pacientes\Enums\EstadoPaciente;

final class PacienteStatusUnchangedException extends PacientesDomainException
{
    public function __construct(EstadoPaciente $estado)
    {
        parent::__construct("El paciente ya se encuentra en estado {$estado->label()}.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_STATUS_UNCHANGED';
    }
}
