<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\SinPermisoTipoEntidadException;
use App\Modules\InscripcionesAtenciones\Exceptions\SinSucursalesAsignadasException;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * [Vigencia y Renovación] RF-001/RF-002/RF-008/RF-009/RF-014.
 *
 * Une ambas fuentes con UNION ALL. No usa vista de BD ni GlobalScope. Las
 * fechas de corte se inyectan (`hoy`, `limite`) para que la regla y el filtro
 * SQL sean equivalentes.
 */
final class VigenciaQueryBuilder
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        array $filters,
        User $actor,
        int $ventanaDias,
        string $hoy,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'fecha_fin',
        string $direction = 'asc',
        bool $panelDefault = false,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));
        $limite = VigenciaPeriod::addDays($hoy, $ventanaDias);

        $query = $this->query($filters, $actor, $ventanaDias, $hoy, $limite, $panelDefault);

        $allowedSorts = [
            'fecha_fin',
            'fecha_inicio',
            'monto_esperado',
            'tipo_entidad',
            'paciente_nombre',
        ];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'fecha_fin';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy('v.'.$sort, $direction)
            ->orderBy('v.tipo_entidad')
            ->orderBy('v.id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<object>
     */
    public function all(
        array $filters,
        User $actor,
        int $ventanaDias,
        string $hoy,
        int $limit = 100,
    ): array {
        $limite = VigenciaPeriod::addDays($hoy, $ventanaDias);

        return $this->query($filters, $actor, $ventanaDias, $hoy, $limite, false)
            ->orderBy('v.fecha_fin')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(
        array $filters,
        User $actor,
        int $ventanaDias,
        string $hoy,
        string $limite,
        bool $panelDefault,
    ): Builder {
        $types = $this->allowedTypes($actor);

        if ($types === []) {
            throw new SinPermisoTipoEntidadException;
        }

        $requestedType = $filters['tipo_entidad'] ?? null;

        if ($requestedType !== null && $requestedType !== '') {
            $requested = TipoEntidadVigencia::tryFrom((string) $requestedType);

            if ($requested === null || ! in_array($requested, $types, true)) {
                throw new SinPermisoTipoEntidadException;
            }

            $types = [$requested];
        }

        $query = DB::query()->fromSub($this->unionQuery(), 'v');
        $this->applyScope($query, $filters, $actor);
        $this->applyFilters($query, $filters, $types, $ventanaDias, $hoy, $limite, $panelDefault);

        return $query;
    }

    private function unionQuery(): Builder
    {
        $inscripciones = DB::table('inscripcion_sala as i')
            ->select([
                'i.id',
                'i.paciente_id',
                'i.tutor_responsable_id',
                'i.sucursal_id',
                'i.servicio_id',
                'i.fecha_inicio',
                'i.fecha_fin',
                'i.monto_esperado',
                'i.estado as estado_almacenado',
                'i.sala_id',
                DB::raw('NULL as profesional_id'),
                DB::raw('NULL as profesional_nombre'),
                DB::raw('NULL as cantidad_sesiones'),
                'paciente.codigo as paciente_codigo',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido_paterno as paciente_apellido_paterno',
                'paciente.apellido_materno as paciente_apellido_materno',
                'paciente.ci as paciente_ci',
                'paciente.estado as paciente_estado',
                'tutor.nombre_completo as tutor_nombre',
                'tutor.telefono as tutor_telefono',
                'tutor.activo as tutor_activo',
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                'servicio.activo as servicio_activo',
                'sala.nombre as sala_nombre',
                'sala.activo as sala_activo',
                DB::raw("'inscripcion_sala' as tipo_entidad"),
                DB::raw(
                    "CASE WHEN i.estado IN ('retirada') THEN 1 ELSE 0 END as retirada",
                ),
                DB::raw(
                    "CASE WHEN i.estado = 'en_pausa' THEN 1 ELSE 0 END as en_pausa",
                ),
                DB::raw(
                    'CASE WHEN EXISTS ('
                    .'SELECT 1 FROM inscripcion_sala r '
                    .'WHERE r.inscripcion_origen_id = i.id AND r.estado <> \'retirada\''
                    .') THEN 1 ELSE 0 END as renovada',
                ),
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'i.paciente_id')
            ->leftJoin('tutor', 'tutor.id', '=', 'i.tutor_responsable_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'i.sucursal_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'i.servicio_id')
            ->leftJoin('sala', 'sala.id', '=', 'i.sala_id');

        $paquetes = DB::table('paquete_sesiones as p')
            ->select([
                'p.id',
                'p.paciente_id',
                'p.tutor_responsable_id',
                'p.sucursal_id',
                'p.servicio_id',
                'p.fecha_inicio',
                'p.fecha_fin',
                'p.monto_esperado',
                'p.estado as estado_almacenado',
                DB::raw('NULL as sala_id'),
                'p.profesional_id',
                'profesional.nombre_completo as profesional_nombre',
                'p.cantidad_sesiones',
                'paciente.codigo as paciente_codigo',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido_paterno as paciente_apellido_paterno',
                'paciente.apellido_materno as paciente_apellido_materno',
                'paciente.ci as paciente_ci',
                'paciente.estado as paciente_estado',
                'tutor.nombre_completo as tutor_nombre',
                'tutor.telefono as tutor_telefono',
                'tutor.activo as tutor_activo',
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                'servicio.activo as servicio_activo',
                DB::raw('NULL as sala_nombre'),
                DB::raw('NULL as sala_activo'),
                DB::raw("'paquete_sesiones' as tipo_entidad"),
                DB::raw(
                    "CASE WHEN p.estado IN ('retirado') THEN 1 ELSE 0 END as retirada",
                ),
                DB::raw(
                    "CASE WHEN p.estado = 'en_pausa' THEN 1 ELSE 0 END as en_pausa",
                ),
                DB::raw(
                    'CASE WHEN EXISTS ('
                    .'SELECT 1 FROM paquete_sesiones r '
                    .'WHERE r.paquete_origen_id = p.id AND r.estado <> \'retirado\''
                    .') THEN 1 ELSE 0 END as renovada',
                ),
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'p.paciente_id')
            ->leftJoin('tutor', 'tutor.id', '=', 'p.tutor_responsable_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'p.sucursal_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'p.servicio_id')
            ->leftJoin('profesional', 'profesional.id', '=', 'p.profesional_id');

        return $inscripciones->unionAll($paquetes);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScope(Builder $query, array $filters, User $actor): void
    {
        $accessible = $this->access->accesibles($actor->id);

        if ($accessible === []) {
            throw new SinSucursalesAsignadasException;
        }

        $requested = isset($filters['sucursal_id']) && $filters['sucursal_id'] !== ''
            ? (int) $filters['sucursal_id']
            : null;

        if ($requested !== null && ! in_array($requested, $accessible, true)) {
            throw new BranchAccessDeniedException;
        }

        $query->whereIn('v.sucursal_id', $accessible);

        if ($requested !== null) {
            $query->where('v.sucursal_id', $requested);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<TipoEntidadVigencia>  $types
     */
    private function applyFilters(
        Builder $query,
        array $filters,
        array $types,
        int $ventanaDias,
        string $hoy,
        string $limite,
        bool $panelDefault,
    ): void {
        $query->whereIn('v.tipo_entidad', array_map(
            static fn (TipoEntidadVigencia $tipo): string => $tipo->value,
            $types,
        ));

        if (! empty($filters['paciente_id'])) {
            $query->where('v.paciente_id', (int) $filters['paciente_id']);
        }

        if (! empty($filters['servicio_id'])) {
            $query->where('v.servicio_id', (int) $filters['servicio_id']);
        }

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower(trim((string) $filters['search'])).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query
                    ->whereRaw('lower(v.paciente_nombre) like ?', [$like])
                    ->orWhereRaw('lower(v.paciente_apellido_paterno) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(v.paciente_apellido_materno, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(v.paciente_codigo, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(v.paciente_ci, \'\')) like ?', [$like]);
            });
        }

        if (! empty($filters['periodo_desde']) && ! empty($filters['periodo_hasta'])) {
            $query
                ->where('v.fecha_fin', '>=', (string) $filters['periodo_desde'])
                ->where('v.fecha_fin', '<=', (string) $filters['periodo_hasta']);
        }

        /*
         * RF-015/RN-009: una inscripción vencida de un paciente retirado no
         * debe aparecer en alertas ni reportes de vencimiento.
         */
        $query->where('v.paciente_estado', '!=', 'retirado');

        $estado = $filters['estado'] ?? null;

        if ($estado === null || $estado === '') {
            $query->where('v.en_pausa', 0);
        }

        if (($estado === null || $estado === '') && $panelDefault) {
            $this->applyPanelDefault($query, $hoy, $limite);

            return;
        }

        $this->applyEstado($query, is_string($estado) ? $estado : null, $hoy, $limite);
    }

    private function applyPanelDefault(Builder $query, string $hoy, string $limite): void
    {
        $query
            ->where('v.retirada', 0)
            ->where('v.en_pausa', 0)
            ->where('v.renovada', 0)
            ->where(function (Builder $query) use ($hoy, $limite): void {
                $query
                    ->where('v.fecha_fin', '<', $hoy)
                    ->orWhere(function (Builder $query) use ($hoy, $limite): void {
                        $query
                            ->where('v.fecha_fin', '>=', $hoy)
                            ->where('v.fecha_fin', '<=', $limite);
                    });
            });
    }

    private function applyEstado(Builder $query, ?string $estado, string $hoy, string $limite): void
    {
        if ($estado === null || $estado === '') {
            return;
        }

        if ($estado === 'retirado') {
            $query->where('v.retirada', 1);

            return;
        }

        if ($estado === 'renovado') {
            $query
                ->where('v.retirada', 0)
                ->where('v.en_pausa', 0)
                ->where('v.renovada', 1);

            return;
        }

        $query
            ->where('v.retirada', 0)
            ->where('v.en_pausa', 0)
            ->where('v.renovada', 0);

        if ($estado === 'vencido') {
            $query->where('v.fecha_fin', '<', $hoy);

            return;
        }

        if ($estado === 'por_vencer') {
            $query
                ->where('v.fecha_fin', '>=', $hoy)
                ->where('v.fecha_fin', '<=', $limite);

            return;
        }

        if ($estado === 'vigente') {
            $query->where('v.fecha_fin', '>', $limite);
        }
    }

    /**
     * @return list<TipoEntidadVigencia>
     */
    private function allowedTypes(User $actor): array
    {
        return VigenciaAbilities::allowedTypes($actor);
    }
}
