<?php

namespace App\Modules\Pacientes\Exceptions;

use App\Modules\Pacientes\Enums\EstadoPaciente;

final class PacienteStatusTransitionNotAllowedException extends PacientesDomainException
{
    public function __construct(EstadoPaciente $from, EstadoPaciente $to)
    {
        parent::__construct("No se puede pasar de {$from->label()} a {$to->label()}.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PATIENT_STATUS_TRANSITION_NOT_ALLOWED';
    }
}
