<?php

namespace App\Modules\Pacientes\Queries\Consentimiento;

use App\Modules\Pacientes\Exceptions\ConsentimientoNotFoundException;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Consentimiento concreto dentro de una ficha (04.03 Fase B).
 */
final class GetConsentimientoQuery
{
    public function __invoke(FichaIngreso $ficha, int $consentimientoId): Consentimiento
    {
        $consentimiento = Consentimiento::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->whereKey($consentimientoId)
            ->with(['tipo', 'tutor', 'usuario', 'revocaA'])
            ->first();

        if ($consentimiento === null) {
            throw new ConsentimientoNotFoundException($consentimientoId);
        }

        return $consentimiento;
    }
}
