<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\Area;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de áreas con color, estado y servicios activos (RF-C09).
 */
final class ListAreasQuery
{
    /**
     * @return Collection<int, Area>
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

        return Area::query()
            ->withCount([
                'servicios as servicios_activos_count' => static fn ($query) => $query->where('activo', true),
                'servicios as servicios_total_count',
            ])
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->get();
    }
}
