<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Modules\Pacientes\Exceptions\ConsentimientoTipoDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación de un tipo de consentimiento (04.03 Fase B).
 */
final class RejectConsentimientoTipoDeletionAction
{
    public function __invoke(): never
    {
        throw new ConsentimientoTipoDeleteNotAllowedException;
    }
}
