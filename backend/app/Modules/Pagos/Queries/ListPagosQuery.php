<?php

namespace App\Modules\Pagos\Queries;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pagos\Models\Pago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado de pagos con filtros, orden en lista blanca y alcance por sucursal
 * (08.01/RF-008, RF-014, RNF-002).
 *
 * Una sucursal fuera de alcance devuelve listado vacío, no error.
 */
final class ListPagosQuery
{
    public const SORT_PERMITIDOS = ['fecha_pago', 'numero_correlativo', 'monto', 'created_at'];

    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(
        array $filtros,
        User $actor,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'fecha_pago',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 50));

        return $this->builder($filtros, $actor, $sort, $direction)
            ->paginate($perPage, ['*'], 'page', max(1, $page))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function builder(
        array $filtros,
        User $actor,
        string $sort = 'fecha_pago',
        string $direction = 'desc',
    ): Builder {
        $query = Pago::query();

        $accesibles = $this->access->accesibles($actor->id);
        $sucursal = isset($filtros['sucursal_id']) && $filtros['sucursal_id'] !== ''
            ? (int) $filtros['sucursal_id']
            : null;

        if ($accesibles === [] || ($sucursal !== null && ! in_array($sucursal, $accesibles, true))) {
            $query->whereRaw('1 = 0');
        } elseif ($sucursal !== null) {
            $query->where('pago.sucursal_id', $sucursal);
        } else {
            $query->porSucursales($accesibles);
        }

        if (! empty($filtros['periodo_desde'])) {
            $query->whereDate('pago.fecha_pago', '>=', (string) $filtros['periodo_desde']);
        }

        if (! empty($filtros['periodo_hasta'])) {
            $query->whereDate('pago.fecha_pago', '<=', (string) $filtros['periodo_hasta']);
        }

        if (! empty($filtros['paciente_id'])) {
            $query->where('pago.paciente_id', (int) $filtros['paciente_id']);
        }

        if (! empty($filtros['numero'])) {
            $query->where('pago.numero_correlativo', 'like', '%'.trim((string) $filtros['numero']).'%');
        }

        if (! empty($filtros['medio_pago'])) {
            $query->where('pago.medio_pago', (string) $filtros['medio_pago']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('pago.estado', (string) $filtros['estado']);
        }

        $sort = in_array($sort, self::SORT_PERMITIDOS, true) ? $sort : 'fecha_pago';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy('pago.'.$sort, $direction)
            ->orderBy('pago.id', $direction);
    }
}
