<?php

namespace App\Modules\Pacientes\Queries\Ficha;

use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Collection;

/**
 * Historial de fichas archivadas de un paciente (04.03 Fase A).
 */
final class ListFichaIngresoHistorialQuery
{
    /**
     * @return Collection<int, FichaIngreso>
     */
    public function __invoke(Paciente $paciente): Collection
    {
        return FichaIngreso::query()
            ->where('paciente_id', $paciente->id)
            ->where('estado', 'archivada')
            ->with('sucursalIngreso')
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->get();
    }
}
