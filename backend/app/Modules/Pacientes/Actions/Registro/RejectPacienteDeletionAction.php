<?php

namespace App\Modules\Pacientes\Actions\Registro;

use App\Modules\Pacientes\Exceptions\PacienteDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación física (04.01/RF-009, 04.01/RN-006).
 */
final class RejectPacienteDeletionAction
{
    public function __invoke(): never
    {
        throw new PacienteDeleteNotAllowedException;
    }
}
