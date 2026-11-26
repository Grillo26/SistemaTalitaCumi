<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de tipos de diagnóstico (RF-D06).
 */
final class ListTiposDiagnosticoQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(array $filters): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'nombre');
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 15)));

        $allowedSort = ['nombre', 'activo', 'dias_estimados', 'updated_at'];
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'nombre';

        return TipoDiagnostico::query()
            ->with(['servicio.area', 'servicio.tipoServicio', 'especialidades'])
            ->withCount('especialidades')
            ->buscar($filters['search'] ?? null)
            ->estado($filters['estado'] ?? null)
            ->especialidad(isset($filters['especialidad_id']) ? (int) $filters['especialidad_id'] : null)
            ->orderBy('tipo_diagnostico.'.$sort, $direction)
            ->orderBy('tipo_diagnostico.id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
