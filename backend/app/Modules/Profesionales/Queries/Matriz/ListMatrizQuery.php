<?php

namespace App\Modules\Profesionales\Queries\Matriz;

use App\Models\User;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Matriz profesional–especialidad–sucursal (03/RF-E08).
 *
 * Es una lectura pura de relaciones activas. Un profesional sin sucursal o sin
 * especialidad no genera filas; el directorio lo muestra con asignable=false.
 */
final class ListMatrizQuery
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function __invoke(
        ?string $search,
        ?int $profesionalId,
        ?int $especialidadId,
        ?int $sucursalId,
        ?string $estado,
        bool $soloAsignables,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        User $actor,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $branchIds = $this->scope->accessibleBranchIds($actor);

        $query = $this->baseQuery();

        if ($branchIds === []) {
            $query->whereRaw('1 = 0');
        } elseif ($branchIds !== null) {
            if ($sucursalId !== null && ! in_array($sucursalId, $branchIds, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('s.id', $branchIds);
            }
        }

        if ($profesionalId !== null) {
            $query->where('p.id', $profesionalId);
        }

        if ($especialidadId !== null) {
            $query->where('e.id', $especialidadId);
        }

        if ($sucursalId !== null) {
            $query->where('s.id', $sucursalId);
        }

        if ($estado === 'activo') {
            $query->where('p.activo', true);
        } elseif ($estado === 'inactivo') {
            $query->where('p.activo', false);
        }

        if ($soloAsignables) {
            $query
                ->where('p.activo', true)
                ->where('e.activo', true)
                ->where('s.activo', true);
        }

        $search = trim((string) $search);

        if ($search !== '') {
            $like = '%'.mb_strtolower($search).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query
                    ->whereRaw('lower(p.nombre_completo) like ?', [$like])
                    ->orWhereRaw('lower(p.ci) like ?', [$like])
                    ->orWhereRaw('lower(e.nombre) like ?', [$like])
                    ->orWhereRaw('lower(s.nombre) like ?', [$like]);
            });
        }

        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $sortColumn = match ($sort) {
            'especialidad_nombre' => 'e.nombre',
            'sucursal_nombre' => 's.nombre',
            'profesional_ci' => 'p.ci',
            'profesional_updated_at' => 'p.updated_at',
            default => 'p.nombre_completo',
        };

        return $query
            ->orderBy($sortColumn, $direction)
            ->orderBy('p.nombre_completo')
            ->orderBy('e.nombre')
            ->orderBy('s.nombre')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    private function baseQuery(): Builder
    {
        return DB::table('profesional_especialidad as pe')
            ->join('profesional as p', 'p.id', '=', 'pe.profesional_id')
            ->join('especialidad as e', 'e.id', '=', 'pe.especialidad_id')
            ->join('profesional_sucursal as ps', function ($join): void {
                $join->on('ps.profesional_id', '=', 'p.id')
                    ->where('ps.activo', true);
            })
            ->join('sucursal as s', 's.id', '=', 'ps.sucursal_id')
            ->where('pe.activo', true)
            ->select([
                'p.id as profesional_id',
                'p.nombre_completo as profesional_nombre',
                'p.ci as profesional_ci',
                'p.activo as profesional_activo',
                'p.updated_at as profesional_updated_at',
                'e.id as especialidad_id',
                'e.nombre as especialidad_nombre',
                'e.activo as especialidad_activo',
                'pe.tipo as tipo',
                's.id as sucursal_id',
                's.codigo as sucursal_codigo',
                's.nombre as sucursal_nombre',
                's.activo as sucursal_activo',
                'ps.activo as relacion_sucursal_activa',
            ]);
    }
}
