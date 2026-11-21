<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de sucursales (RF-004).
 *
 * Lectura pura: nunca invoca Actions de escritura.
 */
final class ListBranchesQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        ?string $search,
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

        $allowedSorts = ['codigo', 'nombre', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query = Branch::query();

        if (! $hasGlobalScope) {
            $accessibleBranchIds = $this->access->accesibles($actorId);

            if ($accessibleBranchIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $accessibleBranchIds);
            }
        }

        return $query
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
