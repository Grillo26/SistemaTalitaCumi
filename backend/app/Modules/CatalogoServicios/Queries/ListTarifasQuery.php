<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Support\TarifaAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de tarifas (RF-T09).
 */
final class ListTarifasQuery
{
    public function __construct(private readonly TarifaAccessScope $scope) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(array $filters, User $actor): LengthAwarePaginator
    {
        $sort = (string) ($filters['sort'] ?? 'vigente_desde');
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 15)));

        $query = Tarifa::query()
            ->select('tarifa.*')
            ->with(['servicio.area', 'categoria', 'sucursal'])
            ->buscar($filters['search'] ?? null)
            ->servicio(isset($filters['servicio_id']) ? (int) $filters['servicio_id'] : null)
            ->categoria(isset($filters['categoria_tarifa_id']) ? (int) $filters['categoria_tarifa_id'] : null)
            ->estadoVigencia($filters['estado_vigencia'] ?? null);

        $this->scope->apply(
            $query,
            $actor,
            isset($filters['sucursal_id']) ? (int) $filters['sucursal_id'] : null,
            (bool) ($filters['solo_global'] ?? false),
        );

        if ($sort === 'servicio') {
            $query->leftJoin('servicio', 'servicio.id', '=', 'tarifa.servicio_id')
                ->orderBy('servicio.nombre', $direction);
        } elseif ($sort === 'categoria') {
            $query->leftJoin('categoria_tarifa', 'categoria_tarifa.id', '=', 'tarifa.categoria_tarifa_id')
                ->orderBy('categoria_tarifa.nombre', $direction);
        } elseif ($sort === 'sucursal') {
            $query->leftJoin('sucursal', 'sucursal.id', '=', 'tarifa.sucursal_id')
                ->orderByRaw('CASE WHEN sucursal.nombre IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sucursal.nombre', $direction);
        } else {
            $allowed = ['monto', 'vigente_desde', 'vigente_hasta', 'updated_at'];
            $column = in_array($sort, $allowed, true) ? $sort : 'vigente_desde';
            $query->orderBy('tarifa.'.$column, $direction);
        }

        return $query
            ->orderBy('tarifa.id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
