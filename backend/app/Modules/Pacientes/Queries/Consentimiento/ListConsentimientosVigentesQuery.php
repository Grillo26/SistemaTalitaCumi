<?php

namespace App\Modules\Pacientes\Queries\Consentimiento;

use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consentimientos otorgados y no revocados de una ficha (04.03 Fase B).
 */
final class ListConsentimientosVigentesQuery
{
    /**
     * @return Collection<int, Consentimiento>
     */
    public function __invoke(FichaIngreso $ficha): Collection
    {
        return Consentimiento::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->vigentes()
            ->with(['tipo', 'tutor', 'usuario'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
    }
}
