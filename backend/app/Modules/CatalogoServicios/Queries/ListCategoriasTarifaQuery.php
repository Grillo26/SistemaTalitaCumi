<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de categorías de tarifa (RF-T01 / RF-T02 / RF-T09).
 */
final class ListCategoriasTarifaQuery
{
    /**
     * @return Collection<int, CategoriaTarifa>
     */
    public function __invoke(
        ?string $search = null,
        ?string $estado = null,
        string $sort = 'nombre',
        string $direction = 'asc',
    ): Collection {
        $allowedSort = ['nombre', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'nombre';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return CategoriaTarifa::query()
            ->withCount([
                'tarifas as tarifas_vigentes_count' => static fn ($query) => $query->vigentes(),
                'tarifas as tarifas_total_count',
            ])
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->get();
    }
}
