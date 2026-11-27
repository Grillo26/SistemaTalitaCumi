<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Support\AtencionQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado filtrable de entrevistas y diagnósticos (RF-008/CA-008/CA-009).
 *
 * El alcance por sucursal es explícito: nunca se usa un GlobalScope.
 */
final class ListAtencionesQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        TipoAtencion $tipo,
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 10,
        string $sort = 'fecha',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $query = $tipo === TipoAtencion::ENTREVISTA
            ? AtencionQueryJoins::entrevistas(AtencionEntrevista::query())
            : AtencionQueryJoins::diagnosticos(AtencionDiagnostico::query());

        $this->applyScope($query, $filters, $actor, $tipo);
        $this->applyFilters($query, $filters, $tipo);

        $allowedSorts = $tipo === TipoAtencion::ENTREVISTA
            ? ['fecha', 'monto_esperado', 'estado', 'updated_at']
            : ['fecha_inicio', 'fecha_fin', 'monto_esperado', 'estado', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true)
            ? $sort
            : ($tipo === TipoAtencion::ENTREVISTA ? 'fecha' : 'fecha_inicio');
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $paginator = $query
            ->orderBy('atencion_'.$this->tableSuffix($tipo).'.'.$sort, $direction)
            ->orderBy('atencion_'.$this->tableSuffix($tipo).'.id', $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        if ($tipo === TipoAtencion::DIAGNOSTICO) {
            $paginator->getCollection()->load('profesionales:id,nombre_completo');
        }

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScope(Builder $query, array $filters, User $actor, TipoAtencion $tipo): void
    {
        $table = 'atencion_'.$this->tableSuffix($tipo);
        $accessible = $this->access->accesibles($actor->id);
        $requestedBranch = isset($filters['sucursal_id']) ? (int) $filters['sucursal_id'] : null;

        if ($requestedBranch !== null && ! in_array($requestedBranch, $accessible, true)) {
            throw new BranchAccessDeniedException;
        }

        if ($accessible === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($table.'.sucursal_id', $accessible);

        if ($requestedBranch !== null) {
            $query->where($table.'.sucursal_id', $requestedBranch);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, TipoAtencion $tipo): void
    {
        $table = 'atencion_'.$this->tableSuffix($tipo);

        if (! empty($filters['paciente_id'])) {
            $query->where($table.'.paciente_id', (int) $filters['paciente_id']);
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
            if ($tipo === TipoAtencion::ENTREVISTA) {
                $query->porPeriodo((string) $filters['periodo_desde'], (string) $filters['periodo_hasta']);
            } else {
                $query->porPeriodo((string) $filters['periodo_desde'], (string) $filters['periodo_hasta']);
            }
        }

        if (! empty($filters['estado'])) {
            $query->where($table.'.estado', (string) $filters['estado']);
        }

        if ($tipo === TipoAtencion::DIAGNOSTICO) {
            if (! empty($filters['tipo_diagnostico_id'])) {
                $query->where('atencion_diagnostico.tipo_diagnostico_id', (int) $filters['tipo_diagnostico_id']);
            }

            if (! empty($filters['profesional_id'])) {
                $query->whereHas('profesionales', static function (Builder $query) use ($filters): void {
                    $query->where('profesional.id', (int) $filters['profesional_id']);
                });
            }
        }
    }

    private function tableSuffix(TipoAtencion $tipo): string
    {
        return $tipo === TipoAtencion::ENTREVISTA ? 'entrevista' : 'diagnostico';
    }
}
