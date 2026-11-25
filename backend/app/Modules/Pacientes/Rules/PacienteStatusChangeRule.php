<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteStatusReasonRequiredException;

/**
 * Motivo del cambio de estado (04.01/RN-005).
 *
 * Obligatorio al retirar, al volver de retirado y —supuesto validado con
 * 06.05/RF-006— al pasar a pausa. Opcional al reactivar desde pausa.
 */
final class PacienteStatusChangeRule
{
    public function reason(EstadoPaciente $from, EstadoPaciente $to, ?string $reason): ?string
    {
        $reason = trim((string) $reason);

        if ($this->requiresReason($from, $to) && $reason === '') {
            throw new PacienteStatusReasonRequiredException;
        }

        return $reason === '' ? null : $reason;
    }

    public function requiresReason(EstadoPaciente $from, EstadoPaciente $to): bool
    {
        return $to === EstadoPaciente::RETIRADO
            || $from === EstadoPaciente::RETIRADO
            || $to === EstadoPaciente::EN_PAUSA;
    }
}
