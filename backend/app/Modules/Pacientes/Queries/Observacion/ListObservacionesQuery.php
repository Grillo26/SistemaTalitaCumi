<?php

namespace App\Modules\Pacientes\Queries\Observacion;

use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Observaciones fechadas de una ficha (04.03 Fase C, RF-006).
 */
final class ListObservacionesQuery
{
    /**
     * @return Collection<int, FichaIngresoObservacion>
     */
    public function __invoke(FichaIngreso $ficha): Collection
    {
        return FichaIngresoObservacion::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->with(['usuario', 'referencia'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }
}
