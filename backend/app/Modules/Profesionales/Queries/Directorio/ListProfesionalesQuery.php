<?php

namespace App\Modules\Profesionales\Queries\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de profesionales con filtros y alcance (03/RF-D04).
 */
final class ListProfesionalesQuery
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function __invoke(
        ?string $search,
        ?int $especialidadId,
        ?int $sucursalId,
        ?string $estado,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        User $actor,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $allowedSorts = ['nombre_completo', 'ci', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre_completo';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query = Profesional::query()->with([
            'relacionesEspecialidad.especialidad',
            'relacionesSucursal.sucursal',
        ]);

        $this->scope->apply($query, $actor, $sucursalId);

        return $query
            ->buscar($search)
            ->especialidad($especialidadId)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
