<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;

/**
 * [Reprogramación y Cancelación] RF-001 / RN-008.
 *
 * Reprogramar exige una entidad origen activa. Un turno sin entidad origen
 * (por ejemplo, diagnóstico de 7.5) no tiene este chequeo.
 */
final class OrigenReprogramableRule
{
    public function permite(?EstadoOrigenProgramacion $estado): bool
    {
        return $estado === null || $estado === EstadoOrigenProgramacion::ACTIVA;
    }

    public function mensaje(?EstadoOrigenProgramacion $estado): ?string
    {
        if ($this->permite($estado)) {
            return null;
        }

        return match ($estado) {
            EstadoOrigenProgramacion::VENCIDA => 'La entidad origen está vencida. Renovela antes de reprogramar.',
            EstadoOrigenProgramacion::EN_PAUSA => 'La entidad origen está en pausa. Reactívela antes de reprogramar.',
            EstadoOrigenProgramacion::RENOVADA => 'La entidad origen fue renovada. Reprogramá desde la entidad vigente.',
            EstadoOrigenProgramacion::RETIRADA => 'La entidad origen fue retirada. No se puede reprogramar.',
            default => 'No se pudo determinar el estado de la entidad origen.',
        };
    }
}
