<?php

namespace App\Modules\Pacientes\Queries\Ficha;

use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoRevision;
use Illuminate\Database\Eloquent\Collection;

/**
 * Revisiones de una ficha (04.03/RF-005).
 */
final class GetFichaIngresoRevisionesQuery
{
    /**
     * @return Collection<int, FichaIngresoRevision>
     */
    public function __invoke(FichaIngreso $ficha): Collection
    {
        return FichaIngresoRevision::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->with('usuario')
            ->orderByDesc('version')
            ->get();
    }
}
