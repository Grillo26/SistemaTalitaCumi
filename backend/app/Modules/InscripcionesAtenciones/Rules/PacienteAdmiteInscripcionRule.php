<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * [Estados Paciente e Inscripción] RF-007 / RN-006 / CA-003.
 *
 * Salas, Paquetes y Atenciones ya aplican esta regla mediante
 * PatientDirectoryContract. No se reimplementa la validación en 06.05.
 */
final class PacienteAdmiteInscripcionRule
{
    public static function permite(EstadoPaciente $estado): bool
    {
        return $estado->permiteInscripcion();
    }
}
