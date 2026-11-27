<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\CatalogoNoDisponibleException;
use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\RangoHorarioRule;
use App\Modules\AgendaTurnos\Rules\ResumenSemanaRule;
use App\Modules\AgendaTurnos\Rules\SemanaRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use App\Modules\AgendaTurnos\Support\DatosOperativosResolver;
use App\Modules\AgendaTurnos\Support\SemanaAgenda;
use App\Modules\AgendaTurnos\Support\TurnoAgendaPresenter;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * [Agenda Semanal] RF-001, RF-002, RF-003, RF-004, RF-005, RF-011, RF-013,
 * RF-014, RN-001 a RN-006, RNF-001, RNF-002, RNF-005.
 */
final class GetAgendaSemana
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly DatosOperativosResolver $datos,
        private readonly ParameterReaderContract $parametros,
        private readonly PermissionEvaluatorContract $permissions,
        private readonly SemanaRule $semanaRule,
        private readonly RangoHorarioRule $rangoRule,
        private readonly ResumenSemanaRule $resumenRule,
        private readonly TurnoAgendaPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function __invoke(User $actor, array $filters): array
    {
        $zona = (string) config('app.timezone', 'America/La_Paz');
        $hoy = CarbonImmutable::now($zona)->startOfDay();
        $diasHabilitados = $this->diasHabilitados();
        $semana = ($this->semanaRule)(
            $this->fechaSemana($filters),
            $hoy,
            $diasHabilitados,
            $zona,
        );

        $branchId = $this->access->resolverSucursal(
            $actor,
            $this->sucursalSolicitada($filters),
        );

        $servicioIdsPorArea = $this->servicioIdsPorArea($filters);
        $query = Turno::query()
            ->deSucursal($branchId)
            ->enRango($semana->inicio, $semana->fin)
            ->ordenado();

        $this->aplicarFiltros($query, $filters, $servicioIdsPorArea);

        $turnos = $query->get();
        $fechasVisibles = [];

        foreach ($semana->diasVisibles() as $dia) {
            $fechasVisibles[$dia->fecha] = true;
        }

        $visibles = $turnos
            ->filter(
                static fn (Turno $turno): bool => isset(
                    $fechasVisibles[$turno->fecha->toDateString()],
                ),
            )
            ->values();
        $ocultos = $turnos
            ->reject(
                static fn (Turno $turno): bool => isset(
                    $fechasVisibles[$turno->fecha->toDateString()],
                ),
            )
            ->values();

        $datos = $this->datos->resolver(
            $visibles->pluck('paciente_id')->map(static fn (mixed $id): int => (int) $id)->all(),
            $visibles->pluck('profesional_id')->filter()->map(static fn (mixed $id): int => (int) $id)->all(),
            $visibles->pluck('sala_id')->filter()->map(static fn (mixed $id): int => (int) $id)->all(),
            false,
        );

        $hoyFecha = $hoy->toDateString();
        $turnosData = $visibles->map(
            fn (Turno $turno): array => $this->presenter->presente(
                $turno,
                $datos,
                $turno->fecha->toDateString() === $hoyFecha,
            ),
        )->all();

        $resumen = ($this->resumenRule)($visibles->pluck('estado')->all());
        $rango = ($this->rangoRule)(
            $this->parametro('agenda.horario_apertura'),
            $this->parametro('agenda.horario_cierre'),
            $visibles->map(static fn (Turno $turno): array => [
                'hora_inicio' => $turno->hora_inicio,
                'hora_fin' => $turno->hora_fin,
            ])->all(),
        );

        $sucursal = $this->access->resumenSucursal($branchId);

        return [
            'data' => [
                'sucursal' => [
                    ...$sucursal->toArray(),
                    'disponible' => true,
                ],
                'semana' => $semana->toArray(),
                'rango_horario' => $rango->toArray(),
                'turnos' => $turnosData,
                'resumen' => $resumen->toArray(),
                'avisos' => $this->avisos($ocultos->all()),
                'disponibilidad' => $datos->disponibilidad(),
                'zona_horaria' => $zona,
            ],
            'can' => [
                'ver' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::VER,
                    $branchId,
                ),
                'exportar' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::EXPORTAR,
                    $branchId,
                ),
            ],
            'filtros' => $this->filtrosNormalizados($filters, $branchId, $semana),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $servicioIdsPorArea
     */
    private function aplicarFiltros(
        Builder $query,
        array $filters,
        ?array $servicioIdsPorArea,
    ): void {
        if ($servicioIdsPorArea !== null) {
            $query->porServicios($servicioIdsPorArea);
        }

        if ($this->valorEntero($filters, 'servicio_id') !== null) {
            $query->where('servicio_id', $this->valorEntero($filters, 'servicio_id'));
        }

        $salaSinAsignar = (bool) ($filters['sala_sin_asignar'] ?? false);
        $salaId = $this->valorEntero($filters, 'sala_id');
        $query->porSala($salaId, $salaSinAsignar);

        $profesionalSinAsignar = (bool) ($filters['profesional_sin_asignar'] ?? false);
        $profesionalId = $this->valorEntero($filters, 'profesional_id');
        $query->porProfesional($profesionalId, $profesionalSinAsignar);

        $estado = $filters['estado'] ?? null;

        if (is_string($estado) && $estado !== '') {
            $query->where('estado', $estado);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>|null
     */
    private function servicioIdsPorArea(array $filters): ?array
    {
        $areaId = $this->valorEntero($filters, 'area_id');

        if ($areaId === null) {
            return null;
        }

        $catalogo = $this->datos->catalogo(false);

        if (! $catalogo['servicios_disponibles']) {
            throw new CatalogoNoDisponibleException;
        }

        return array_values(array_map(
            static fn ($servicio): int => $servicio->id,
            array_filter(
                $catalogo['servicios'],
                static fn ($servicio): bool => $servicio->areaId === $areaId,
            ),
        ));
    }

    /**
     * @param  list<Turno>  $turnos
     * @return list<array<string, mixed>>
     */
    private function avisos(array $turnos): array
    {
        if ($turnos === []) {
            return [];
        }

        $porDia = [];

        foreach ($turnos as $turno) {
            $fecha = $turno->fecha->toDateString();
            $porDia[$fecha] = ($porDia[$fecha] ?? 0) + 1;
        }

        ksort($porDia);
        $cantidad = array_sum($porDia);
        $sustantivo = $cantidad === 1 ? 'turno' : 'turnos';

        return [[
            'codigo' => 'TURNOS_DIAS_NO_VISIBLES',
            'cantidad' => $cantidad,
            'dias' => array_keys($porDia),
            'mensaje' => "Hay {$cantidad} {$sustantivo} en días no visibles de la semana.",
        ]];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function fechaSemana(array $filters): ?string
    {
        $semana = $filters['semana'] ?? null;

        return is_string($semana) && $semana !== '' ? $semana : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sucursalSolicitada(array $filters): ?int
    {
        return $this->valorEntero($filters, 'sucursal_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function valorEntero(array $filters, string $key): ?int
    {
        $value = $filters[$key] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @return list<int>
     */
    private function diasHabilitados(): array
    {
        $raw = $this->parametro('agenda.dias_semana_habilitados');
        $dias = array_values(array_filter(array_map(
            static fn (string $dia): int => (int) trim($dia),
            explode(',', (string) $raw),
        ), static fn (int $dia): bool => $dia > 0));

        return $dias;
    }

    private function parametro(string $clave): mixed
    {
        try {
            return $this->parametros->obtener($clave)->valor;
        } catch (ParameterNotFoundException) {
            throw new ConfiguracionAgendaIncompletaException(
                "Falta el parámetro de agenda {$clave}.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filtrosNormalizados(array $filters, int $branchId, SemanaAgenda $semana): array
    {
        return [
            'sucursal_id' => $branchId,
            'semana' => $semana->inicio,
            'area_id' => $this->valorEntero($filters, 'area_id'),
            'sala_id' => $this->valorEntero($filters, 'sala_id'),
            'sala_sin_asignar' => (bool) ($filters['sala_sin_asignar'] ?? false),
            'profesional_id' => $this->valorEntero($filters, 'profesional_id'),
            'profesional_sin_asignar' => (bool) ($filters['profesional_sin_asignar'] ?? false),
            'servicio_id' => $this->valorEntero($filters, 'servicio_id'),
            'estado' => $filters['estado'] ?? null,
        ];
    }
}
