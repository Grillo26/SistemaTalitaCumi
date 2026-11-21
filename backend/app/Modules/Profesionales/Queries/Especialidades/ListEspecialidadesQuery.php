<?php

namespace App\Modules\Profesionales\Queries\Especialidades;

use App\Modules\Profesionales\Models\Especialidad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de especialidades (03/RF-E03).
 */
final class ListEspecialidadesQuery
{
    public function __invoke(
        ?string $search,
        ?string $estado,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $allowedSorts = ['nombre', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return Especialidad::query()
            ->withCount('profesionalesActivos')
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
