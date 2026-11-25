<?php

namespace App\Modules\Pacientes\Queries\Consentimiento;

use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;
use Illuminate\Database\Eloquent\Collection;

/**
 * Historial completo de consentimientos de una ficha (04.03 Fase B).
 */
final class ListConsentimientosQuery
{
    /**
     * @return Collection<int, Consentimiento>
     */
    public function __invoke(FichaIngreso $ficha): Collection
    {
        return Consentimiento::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->with(['tipo', 'tutor', 'usuario', 'revocaA'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }
}
