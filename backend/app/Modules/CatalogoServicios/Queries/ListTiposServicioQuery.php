<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\TipoServicio;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de tipos de servicio configurables (RN-C09).
 */
final class ListTiposServicioQuery
{
    /**
     * @return Collection<int, TipoServicio>
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

        return TipoServicio::query()
            ->withCount('servicios as servicios_total_count')
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->get();
    }
}
