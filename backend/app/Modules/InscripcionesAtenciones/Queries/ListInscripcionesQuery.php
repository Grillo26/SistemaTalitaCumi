<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\InscripcionesAtenciones\Support\InscripcionQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado filtrable de inscripciones a salas grupales (RF-007 / RF-016).
 *
 * El alcance por sucursal es explícito: nunca se usa un GlobalScope ni se
 * devuelven todas las sucursales como atajo.
 */
final class ListInscripcionesQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 10,
        string $sort = 'fecha_inicio',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        return $this->builder($filters, $actor, $sort, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function builder(
        array $filters,
        User $actor,
        string $sort = 'fecha_inicio',
        string $direction = 'desc',
    ): Builder {
        $query = InscripcionQueryJoins::apply(InscripcionSala::query());

        $this->applyScope($query, $filters, $actor);
        $this->applyFilters($query, $filters);

        $allowedSorts = ['fecha_inicio', 'fecha_fin', 'estado', 'monto_esperado', 'created_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'fecha_inicio';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy('inscripcion_sala.'.$sort, $direction)
            ->orderBy('inscripcion_sala.id', $direction);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScope(Builder $query, array $filters, User $actor): void
    {
        $accessible = $this->access->accesibles($actor->id);
        $requestedBranch = isset($filters['sucursal_id']) ? (int) $filters['sucursal_id'] : null;

        if ($requestedBranch !== null && ! in_array($requestedBranch, $accessible, true)) {
            throw new BranchAccessDeniedException;
        }

        if ($accessible === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('inscripcion_sala.sucursal_id', $accessible);

        if ($requestedBranch !== null) {
            $query->where('inscripcion_sala.sucursal_id', $requestedBranch);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['sala_id'])) {
            $query->where('inscripcion_sala.sala_id', (int) $filters['sala_id']);
        }

        if (! empty($filters['paciente_id'])) {
            $query->where('inscripcion_sala.paciente_id', (int) $filters['paciente_id']);
        }

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower(trim((string) $filters['search'])).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query
                    ->whereRaw('lower(paciente.nombre) like ?', [$like])
                    ->orWhereRaw('lower(paciente.apellido_paterno) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(paciente.apellido_materno, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(paciente.codigo, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(paciente.ci, \'\')) like ?', [$like]);
            });
        }

        if (! empty($filters['periodo_mes']) && ! empty($filters['periodo_anio'])) {
            $range = InscripcionPeriod::monthRange(
                (int) $filters['periodo_mes'],
                (int) $filters['periodo_anio'],
            );

            $query->porPeriodo($range['desde'], $range['hasta']);
        }

        $estado = $filters['estado'] ?? null;
        $today = InscripcionPeriod::today();

        if ($estado === 'activa') {
            $query->where('inscripcion_sala.estado', 'activa')
                ->whereDate('inscripcion_sala.fecha_fin', '>=', $today);
        } elseif ($estado === 'vencida') {
            $query->where('inscripcion_sala.estado', 'activa')
                ->whereDate('inscripcion_sala.fecha_fin', '<', $today);
        } elseif (in_array($estado, ['en_pausa', 'renovada', 'retirada'], true)) {
            $query->where('inscripcion_sala.estado', $estado);
        }
    }
}
