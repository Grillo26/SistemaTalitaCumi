<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado filtrable de paquetes (RF-011 / RF-017).
 */
final class ListPaquetesQuery
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
        $query = PaqueteQueryJoins::apply(PaqueteSesiones::query());

        $this->applyScope($query, $filters, $actor);
        $this->applyFilters($query, $filters);

        $allowed = ['fecha_inicio', 'fecha_fin', 'estado', 'monto_esperado', 'cantidad_sesiones', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'fecha_inicio';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy('paquete_sesiones.'.$sort, $direction)
            ->orderBy('paquete_sesiones.id', $direction);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScope(Builder $query, array $filters, User $actor): void
    {
        $accessible = $this->access->accesibles($actor->id);
        $requested = isset($filters['sucursal_id']) ? (int) $filters['sucursal_id'] : null;

        if ($requested !== null && ! in_array($requested, $accessible, true)) {
            throw new BranchAccessDeniedException;
        }

        if ($accessible === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('paquete_sesiones.sucursal_id', $accessible);

        if ($requested !== null) {
            $query->where('paquete_sesiones.sucursal_id', $requested);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['paciente_id'])) {
            $query->where('paquete_sesiones.paciente_id', (int) $filters['paciente_id']);
        }

        if (! empty($filters['servicio_id'])) {
            $query->where('paquete_sesiones.servicio_id', (int) $filters['servicio_id']);
        }

        if (! empty($filters['profesional_id'])) {
            $query->where('paquete_sesiones.profesional_id', (int) $filters['profesional_id']);
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

        if (! empty($filters['periodo_desde']) && ! empty($filters['periodo_hasta'])) {
            $query->porPeriodo(
                (string) $filters['periodo_desde'],
                (string) $filters['periodo_hasta'],
            );
        }

        $estado = $filters['estado'] ?? null;
        $today = PaquetePeriod::today();

        if ($estado === 'activo') {
            $query->where('paquete_sesiones.estado', 'activo')
                ->whereDate('paquete_sesiones.fecha_fin', '>=', $today);
        } elseif ($estado === 'vencido') {
            $query->where(function (Builder $query) use ($today): void {
                $query->where(function (Builder $query) use ($today): void {
                    $query->where('paquete_sesiones.estado', 'activo')
                        ->whereDate('paquete_sesiones.fecha_fin', '<', $today);
                })->orWhere('paquete_sesiones.estado', 'vencido');
            });
        } elseif (in_array($estado, ['en_pausa', 'renovado', 'retirado'], true)) {
            $query->where('paquete_sesiones.estado', $estado);
        }
    }
}
