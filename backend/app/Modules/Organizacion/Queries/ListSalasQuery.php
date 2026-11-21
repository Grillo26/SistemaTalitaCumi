<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Sala;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de salas (RF-008) con scope por sucursal.
 */
final class ListSalasQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        ?string $search,
        ?int $sucursalId,
        ?int $tipoSalaId,
        ?int $turnoCatalogoId,
        ?string $estado,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        int $actorId,
        bool $hasGlobalScope,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $allowedSorts = ['nombre', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query = Sala::query()->with(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);

        if (! $hasGlobalScope) {
            $accessibleBranchIds = $this->access->accesibles($actorId);

            if ($accessibleBranchIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('sucursal_id', $accessibleBranchIds);
            }
        }

        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        if ($tipoSalaId !== null) {
            $query->where('tipo_sala_id', $tipoSalaId);
        }

        if ($turnoCatalogoId !== null) {
            $query->where('turno_catalogo_id', $turnoCatalogoId);
        }

        return $query
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
