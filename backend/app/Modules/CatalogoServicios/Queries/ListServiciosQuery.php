<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\Servicio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de servicios con filtros (RF-C08).
 */
final class ListServiciosQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(array $filters): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'nombre');
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 15)));

        $query = Servicio::query()
            ->select('servicio.*')
            ->with(['area', 'tipoServicio'])
            ->buscar($filters['search'] ?? null)
            ->area(isset($filters['area_id']) ? (int) $filters['area_id'] : null)
            ->tipoServicio(isset($filters['tipo_servicio_id']) ? (int) $filters['tipo_servicio_id'] : null)
            ->estado($filters['estado'] ?? null);

        if ($sort === 'area') {
            $query->leftJoin('area', 'area.id', '=', 'servicio.area_id')
                ->orderBy('area.nombre', $direction);
        } elseif ($sort === 'tipo') {
            $query->leftJoin('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
                ->orderBy('tipo_servicio.nombre', $direction);
        } else {
            $allowed = ['nombre', 'duracion_minutos', 'activo', 'updated_at'];
            $column = in_array($sort, $allowed, true) ? $sort : 'nombre';
            $query->orderBy('servicio.'.$column, $direction);
        }

        return $query
            ->orderBy('servicio.id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
