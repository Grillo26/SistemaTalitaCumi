<?php

namespace App\Modules\Pacientes\Actions\Observacion;

use App\Modules\Pacientes\Exceptions\ObservacionDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación de una observación (04.03 Fase C).
 */
final class RejectObservacionDeletionAction
{
    public function __invoke(): never
    {
        throw new ObservacionDeleteNotAllowedException;
    }
}
