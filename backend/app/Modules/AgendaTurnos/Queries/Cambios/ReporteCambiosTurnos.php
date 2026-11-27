<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AccionCambioTurno;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * [Reprogramación y Cancelación] RF-012 / FP-04 / RNF-010.
 *
 * Reporte paginado de turnos reprogramados y cancelados. La fecha del evento
 * es la de la bitácora (no la fecha del turno). Solo sucursales accesibles y
 * datos operativos, sin información clínica.
 */
final class ReporteCambiosTurnos
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly DirectoriosAgenda $nombres,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function __invoke(
        array $filtros,
        User $actor,
        int $page = 1,
        int $perPage = 20,
    ): array {
        $accesibles = $this->access->accesibles($actor);
        $sucursalId = isset($filtros['sucursal_id']) ? (int) $filtros['sucursal_id'] : null;

        if ($sucursalId !== null && ! in_array($sucursalId, $accesibles, true)) {
            throw new BranchAccessDeniedException;
        }

        $base = BitacoraEntry::query()
            ->with(['usuario:id,name', 'turno.motivoTurno'])
            ->where('entidad', 'turno')
            ->whereIn('accion', [
                AccionCambioTurno::REPROGRAMAR->value,
                AccionCambioTurno::CANCELAR->value,
                AccionCambioTurno::CANCELAR_MASIVO->value,
            ])
            ->whereIn('sucursal_id', $accesibles);

        $this->aplicarFiltros($base, $filtros, $sucursalId);

        $conteos = [
            'reprogramados' => (clone $base)
                ->where('accion', AccionCambioTurno::REPROGRAMAR->value)
                ->count(),
            'cancelados' => (clone $base)
                ->whereIn('accion', [
                    AccionCambioTurno::CANCELAR->value,
                    AccionCambioTurno::CANCELAR_MASIVO->value,
                ])
                ->count(),
        ];

        $paginator = $base
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 50)), ['*'], 'page', max(1, $page));

        $entradas = $paginator->getCollection();
        $turnos = $entradas->pluck('turno')->filter();
        $pacientes = $this->nombres->pacientes($turnos->pluck('paciente_id')->map(static fn ($id): int => (int) $id)->all());
        $servicios = $this->nombres->servicios($turnos->pluck('servicio_id')->map(static fn ($id): int => (int) $id)->all());
        $profesionales = $this->nombres->profesionales(array_values(array_filter($turnos->pluck('profesional_id')->map(static fn ($id): ?int => $id === null ? null : (int) $id)->all())));

        return [
            'data' => $entradas->map(fn (BitacoraEntry $entry): array => $this->fila(
                $entry,
                $pacientes,
                $servicios,
                $profesionales,
            ))->all(),
            'conteos' => $conteos,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filtros' => $filtros,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros, ?int $sucursalId): void
    {
        if ($sucursalId !== null) {
            $query->where('sucursal_id', $sucursalId);
        }

        $tipo = $filtros['tipo_cambio'] ?? null;

        if ($tipo === 'reprogramado') {
            $query->where('accion', AccionCambioTurno::REPROGRAMAR->value);
        } elseif ($tipo === 'cancelado') {
            $query->whereIn('accion', [
                AccionCambioTurno::CANCELAR->value,
                AccionCambioTurno::CANCELAR_MASIVO->value,
            ]);
        }

        if (isset($filtros['periodo_desde'])) {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat(
                '!Y-m-d',
                (string) $filtros['periodo_desde'],
                (string) config('app.timezone'),
            )->startOfDay());
        }

        if (isset($filtros['periodo_hasta'])) {
            $query->where('created_at', '<=', CarbonImmutable::createFromFormat(
                '!Y-m-d',
                (string) $filtros['periodo_hasta'],
                (string) config('app.timezone'),
            )->endOfDay());
        }

        foreach (['motivo_id' => 'motivo_turno_id', 'paciente_id' => 'paciente_id', 'servicio_id' => 'servicio_id'] as $filtro => $columna) {
            if (isset($filtros[$filtro])) {
                $query->whereHas('turno', static fn (Builder $sub) => $sub->where($columna, (int) $filtros[$filtro]));
            }
        }
    }

    /**
     * @param  array<int, string>  $pacientes
     * @param  array<int, string>  $servicios
     * @param  array<int, string>  $profesionales
     * @return array<string, mixed>
     */
    private function fila(
        BitacoraEntry $entry,
        array $pacientes,
        array $servicios,
        array $profesionales,
    ): array {
        $turno = $entry->turno;
        $antes = $entry->datos_anteriores ?? [];
        $despues = $entry->datos_nuevos ?? [];
        $tipo = $entry->accion === AccionCambioTurno::REPROGRAMAR->value
            ? 'reprogramado'
            : 'cancelado';
        $pacienteId = $turno?->paciente_id;
        $servicioId = $turno?->servicio_id;
        $profesionalId = $turno?->profesional_id;

        return [
            'id' => (int) $entry->id,
            'tipo_cambio' => $tipo,
            'accion' => (string) $entry->accion,
            'fecha_evento' => $entry->created_at?->toIso8601String(),
            'usuario' => $entry->usuario?->name,
            'sucursal_id' => (int) $entry->sucursal_id,
            'turno_id' => $turno === null ? null : (int) $turno->id,
            'turno_nuevo_id' => isset($despues['turno_nuevo_id']) ? (int) $despues['turno_nuevo_id'] : null,
            'fecha_turno' => $turno?->fecha?->toDateString() ?? ($antes['fecha'] ?? null),
            'hora_inicio' => $turno?->hora_inicio ?? ($antes['hora_inicio'] ?? null),
            'fecha_nueva' => $tipo === 'reprogramado' ? ($despues['fecha'] ?? null) : null,
            'hora_nueva' => $tipo === 'reprogramado' ? ($despues['hora_inicio'] ?? null) : null,
            'paciente' => $pacienteId === null ? null : [
                'id' => (int) $pacienteId,
                'nombre' => $pacientes[(int) $pacienteId] ?? DirectoriosAgenda::NO_DISPONIBLE,
            ],
            'servicio' => $servicioId === null ? null : [
                'id' => (int) $servicioId,
                'nombre' => $servicios[(int) $servicioId] ?? DirectoriosAgenda::NO_DISPONIBLE,
            ],
            'profesional' => $profesionalId === null ? null : [
                'id' => (int) $profesionalId,
                'nombre' => $profesionales[(int) $profesionalId] ?? DirectoriosAgenda::NO_DISPONIBLE,
            ],
            'motivo' => $turno?->motivoTurno?->nombre
                ?? ($despues['motivo_nombre'] ?? null),
            'motivo_codigo' => $despues['motivo_codigo'] ?? null,
            'consume_saldo' => $despues['consume_saldo'] ?? null,
            'lote_id' => $despues['lote_id'] ?? null,
            'critico' => (bool) $entry->critico,
        ];
    }
}
