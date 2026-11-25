<?php

namespace App\Modules\Pacientes\Queries\Observacion;

use App\Modules\Pacientes\Exceptions\ObservacionNotFoundException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;

/**
 * Observación concreta dentro de una ficha (04.03 Fase C).
 */
final class GetObservacionQuery
{
    public function __invoke(FichaIngreso $ficha, int $observacionId): FichaIngresoObservacion
    {
        $observacion = FichaIngresoObservacion::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->whereKey($observacionId)
            ->with(['usuario', 'referencia'])
            ->first();

        if ($observacion === null) {
            throw new ObservacionNotFoundException($observacionId);
        }

        return $observacion;
    }
}
