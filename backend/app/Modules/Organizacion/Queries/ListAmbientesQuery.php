<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Ambiente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de ambientes (RF-008) con scope por sucursal.
 */
final class ListAmbientesQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        ?string $search,
        ?int $sucursalId,
        ?string $uso,
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

        $allowedSorts = ['nombre', 'uso', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query = Ambiente::query()->with('sucursal');

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

        return $query
            ->buscar($search)
            ->uso($uso)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
