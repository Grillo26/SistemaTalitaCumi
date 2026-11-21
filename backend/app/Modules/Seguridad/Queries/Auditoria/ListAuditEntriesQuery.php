<?php

namespace App\Modules\Seguridad\Queries\Auditoria;

use App\Models\User;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\AuditDateRange;
use App\Modules\Seguridad\Support\AuditModuleResolver;
use App\Modules\Seguridad\Support\AuditScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta paginada de bitácora con filtros y scoping (02/RF-B04–B06, B14).
 */
final class ListAuditEntriesQuery
{
    public function __construct(private readonly AuditScope $scope) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(array $filters, User $actor): LengthAwarePaginator
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));

        $query = BitacoraEntry::query()
            ->with([
                'usuario:id,name,email',
                'sucursal:id,codigo,nombre',
            ]);

        $this->scope->apply($query, $actor);

        $from = AuditDateRange::start($filters['fecha_desde'] ?? null);
        $to = AuditDateRange::end($filters['fecha_hasta'] ?? null);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        if (! empty($filters['usuario_id'])) {
            $query->where('usuario_id', (int) $filters['usuario_id']);
        }

        if (! empty($filters['modulo'])) {
            $module = (string) $filters['modulo'];
            $entities = AuditModuleResolver::entitiesFor($module);

            $query->where(function (Builder $query) use ($module, $entities): void {
                $query->where('modulo', $module);

                if ($entities !== []) {
                    $query->orWhere(static fn (Builder $query) => $query
                        ->whereNull('modulo')
                        ->whereIn('entidad', $entities));
                }
            });
        }

        if (! empty($filters['entidad'])) {
            $query->where('entidad', (string) $filters['entidad']);
        }

        if (! empty($filters['entidad_id'])) {
            $query->where('entidad_id', (int) $filters['entidad_id']);
        }

        if (! empty($filters['accion'])) {
            $query->where('accion', (string) $filters['accion']);
        }

        if (! empty($filters['resultado'])) {
            $query->where('resultado', (string) $filters['resultado']);
        }

        if (array_key_exists('critico', $filters) && $filters['critico'] !== null) {
            $query->where('critico', (bool) $filters['critico']);
        }

        if (! empty($filters['sucursal_id'])) {
            $query->where('sucursal_id', (int) $filters['sucursal_id']);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
