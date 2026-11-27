<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\SinPermisoTipoEntidadException;
use App\Modules\InscripcionesAtenciones\Exceptions\SinSucursalesAsignadasException;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * [Estados Paciente e Inscripción] RF-011 / RF-014 / CA-011 / CA-012.
 *
 * Une salas y paquetes con UNION ALL. El estado efectivo se filtra en SQL
 * con las mismas condiciones que EstadoEfectivoInscripcionRule: Retirada >
 * EnPausa > Renovada > Vencida > Activa.
 */
final class EstadoInscripcionQueryBuilder
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'fecha_fin',
        string $direction = 'asc',
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));
        $query = $this->query($filters, $actor);
        $this->applySort($query, $sort, $direction);

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function conteos(array $filters, User $actor): array
    {
        $conteos = [];

        foreach (EstadoInscripcion::cases() as $estado) {
            $conteos[$estado->value] = $this->query(
                array_merge($filters, ['estado' => $estado->value]),
                $actor,
            )->count();
        }

        return $conteos;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters, User $actor): Builder
    {
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

        $today = VigenciaPeriod::today();
        $query = DB::query()->fromSub($this->unionQuery(), 'v');

        $this->applyScope($query, $filters, $actor);
        $query->whereIn('v.tipo_entidad', array_map(
            static fn (TipoEntidadVigencia $tipo): string => $tipo->value,
            $types,
        ));

        $this->applyFilters($query, $filters, $today);
        $this->applyEstado($query, $filters['estado'] ?? null, $today);

        return $query;
    }

    private function unionQuery(): Builder
    {
        $sala = DB::table('inscripcion_sala as i')
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
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                'sala.nombre as sala_nombre',
                DB::raw("'inscripcion_sala' as tipo_entidad"),
                DB::raw("CASE WHEN i.estado = 'retirada' THEN 1 ELSE 0 END as retirada"),
                DB::raw("CASE WHEN i.estado = 'en_pausa' THEN 1 ELSE 0 END as en_pausa"),
                DB::raw("CASE WHEN i.estado = 'renovada' THEN 1 ELSE 0 END as renovada_almacenada"),
                DB::raw(
                    'CASE WHEN i.estado = \'renovada\' OR EXISTS ('
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

        $paquete = DB::table('paquete_sesiones as p')
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
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                DB::raw('NULL as sala_nombre'),
                DB::raw("'paquete_sesiones' as tipo_entidad"),
                DB::raw("CASE WHEN p.estado = 'retirado' THEN 1 ELSE 0 END as retirada"),
                DB::raw("CASE WHEN p.estado = 'en_pausa' THEN 1 ELSE 0 END as en_pausa"),
                DB::raw("CASE WHEN p.estado = 'renovado' THEN 1 ELSE 0 END as renovada_almacenada"),
                DB::raw(
                    'CASE WHEN p.estado = \'renovado\' OR EXISTS ('
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

        return $sala->unionAll($paquete);
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
     */
    private function applyFilters(Builder $query, array $filters, string $today): void
    {
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
                ->where('v.fecha_inicio', '<=', (string) $filters['periodo_hasta'])
                ->where('v.fecha_fin', '>=', (string) $filters['periodo_desde']);
        }
    }

    private function applyEstado(Builder $query, mixed $estado, string $today): void
    {
        if (! is_string($estado) || $estado === '') {
            return;
        }

        if ($estado === EstadoInscripcion::RETIRADA->value) {
            $query->where('v.retirada', 1);

            return;
        }

        $query->where('v.retirada', 0);

        if ($estado === EstadoInscripcion::EN_PAUSA->value) {
            $query->where('v.en_pausa', 1);

            return;
        }

        $query->where('v.en_pausa', 0);

        if ($estado === EstadoInscripcion::RENOVADA->value) {
            $query->where('v.renovada', 1);

            return;
        }

        $query->where('v.renovada', 0);

        if ($estado === EstadoInscripcion::VENCIDA->value) {
            $query->where(function (Builder $query) use ($today): void {
                $query
                    ->whereIn('v.estado_almacenado', ['vencida', 'vencido'])
                    ->orWhere('v.fecha_fin', '<', $today);
            });

            return;
        }

        if ($estado === EstadoInscripcion::ACTIVA->value) {
            $query
                ->whereNotIn('v.estado_almacenado', ['vencida', 'vencido'])
                ->where('v.fecha_fin', '>=', $today);
        }
    }

    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $allowed = ['fecha_fin', 'fecha_inicio', 'monto_esperado', 'tipo_entidad', 'paciente_nombre'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'fecha_fin';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query
            ->orderBy('v.'.$sort, $direction)
            ->orderBy('v.tipo_entidad')
            ->orderBy('v.id');
    }

    /**
     * @return list<TipoEntidadVigencia>
     */
    private function allowedTypes(User $actor): array
    {
        $types = [];

        if ($actor->can(InscripcionPermission::VIEW)) {
            $types[] = TipoEntidadVigencia::INSCRIPCION_SALA;
        }

        if ($actor->can(PaquetePermission::VIEW)) {
            $types[] = TipoEntidadVigencia::PAQUETE_SESIONES;
        }

        return $types;
    }
}
