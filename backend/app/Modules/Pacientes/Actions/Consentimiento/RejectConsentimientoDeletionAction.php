<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Modules\Pacientes\Exceptions\ConsentimientoDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación de un consentimiento (04.03 Fase B).
 */
final class RejectConsentimientoDeletionAction
{
    public function __invoke(): never
    {
        throw new ConsentimientoDeleteNotAllowedException;
    }
}
