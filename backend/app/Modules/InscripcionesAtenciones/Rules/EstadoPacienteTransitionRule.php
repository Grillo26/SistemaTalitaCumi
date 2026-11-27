<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteStatusTransitionNotAllowedException;

/**
 * [Estados Paciente e Inscripción] RF-003 / RN-002.
 *
 * La matriz autoritativa del paciente ya vive en Pacientes (Enum +
 * PacienteStatusTransitionRule). Esta regla es un adaptador puro para el
 * submódulo 06.05 y evita duplicar la tabla.
 */
final class EstadoPacienteTransitionRule
{
    public static function permite(EstadoPaciente $desde, EstadoPaciente $hacia): bool
    {
        return $desde->canTransitionTo($hacia);
    }

    public static function assertPermitida(EstadoPaciente $desde, EstadoPaciente $hacia): void
    {
        if (! self::permite($desde, $hacia)) {
            throw new PacienteStatusTransitionNotAllowedException($desde, $hacia);
        }
    }

    /**
     * @return list<EstadoPaciente>
     */
    public static function destinosPermitidos(EstadoPaciente $desde): array
    {
        return $desde->allowedTransitions();
    }
}
