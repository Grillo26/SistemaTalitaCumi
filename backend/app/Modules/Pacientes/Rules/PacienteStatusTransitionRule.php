<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteStatusTransitionNotAllowedException;

/**
 * Transiciones válidas del paciente (04.01/RN-004, 06.05/RF-003).
 */
final class PacienteStatusTransitionRule
{
    public function assertAllowed(EstadoPaciente $from, EstadoPaciente $to): void
    {
        if (! $from->canTransitionTo($to)) {
            throw new PacienteStatusTransitionNotAllowedException($from, $to);
        }
    }
}
