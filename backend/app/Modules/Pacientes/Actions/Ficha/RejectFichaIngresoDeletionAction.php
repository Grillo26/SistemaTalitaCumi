<?php

namespace App\Modules\Pacientes\Actions\Ficha;

use App\Modules\Pacientes\Exceptions\FichaIngresoDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación física (04.03/RF-014).
 */
final class RejectFichaIngresoDeletionAction
{
    public function __invoke(): never
    {
        throw new FichaIngresoDeleteNotAllowedException;
    }
}
