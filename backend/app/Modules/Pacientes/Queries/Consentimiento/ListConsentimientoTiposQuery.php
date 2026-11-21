<?php

namespace App\Modules\Pacientes\Queries\Consentimiento;

use App\Modules\Pacientes\Models\ConsentimientoTipo;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catálogo de tipos de consentimiento (04.03 Fase B).
 */
final class ListConsentimientoTiposQuery
{
    /**
     * @return Collection<int, ConsentimientoTipo>
     */
    public function __invoke(bool $incluirInactivos = false): Collection
    {
        return ConsentimientoTipo::query()
            ->when(! $incluirInactivos, static fn ($query) => $query->activos())
            ->ordenados()
            ->get();
    }
}
