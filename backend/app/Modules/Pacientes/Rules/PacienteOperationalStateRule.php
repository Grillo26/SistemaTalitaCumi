<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * Efecto del estado en la operación (04.01/RN-007).
 *
 * Activo: puede inscribirse y recibir turnos nuevos.
 * En pausa: conserva inscripciones, no recibe turnos nuevos.
 * Retirado: no recibe inscripciones ni turnos nuevos.
 */
final class PacienteOperationalStateRule
{
    public function permiteInscripcion(EstadoPaciente $estado): bool
    {
        return $estado->permiteInscripcion();
    }

    public function permiteTurnoNuevo(EstadoPaciente $estado): bool
    {
        return $estado->permiteTurnoNuevo();
    }
}
