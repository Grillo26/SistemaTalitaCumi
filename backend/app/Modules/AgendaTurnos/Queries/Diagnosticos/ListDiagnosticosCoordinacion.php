<?php

namespace App\Modules\AgendaTurnos\Queries\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use App\Modules\AgendaTurnos\Support\DiagnosticoTurnosResolver;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Throwable;

/**
 * 07.05/RF-006 / 07.05/RF-008 / 07.05/RF-011 / 07.05/RN-008 /
 * 07.05/CA-002 / 07.05/CA-008 / 07.05/CA-009.
 *
 * Listado y reporte operativo. Resuelve nombres en lote (sin N+1 por fila) y
 * no audita la consulta: solo el detalle de coordinación se audita.
 */
final class ListDiagnosticosCoordinacion
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly DiagnosticoTurnosResolver $turnos,
        private readonly EstadoGeneralDiagnosticoRule $rule,
        private readonly PatientDirectoryContract $pacientes,
        private readonly BranchDirectoryContract $sucursales,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly AgendaClock $clock,
        private readonly RegeneracionRule $futuro,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function __invoke(array $filtros, User $actor): array
    {
        $accesibles = $this->access->accesibles($actor);
        $sucursalId = isset($filtros['sucursal_id']) ? (int) $filtros['sucursal_id'] : null;

        if ($sucursalId !== null && ! in_array($sucursalId, $accesibles, true)) {
            throw new BranchAccessDeniedException;
        }

        $pagina = $this->diagnosticos->buscar(
            sucursalIds: $sucursalId === null ? $accesibles : [$sucursalId],
            desde: $this->texto($filtros['desde'] ?? null),
            hasta: $this->texto($filtros['hasta'] ?? null),
            tipoDiagnosticoId: isset($filtros['tipo_diagnostico_id'])
                ? (int) $filtros['tipo_diagnostico_id']
                : null,
            estadoGeneral: $this->texto($filtros['estado_general'] ?? null),
            profesionalId: isset($filtros['profesional_id'])
                ? (int) $filtros['profesional_id']
                : null,
            page: max(1, (int) ($filtros['page'] ?? 1)),
            perPage: min(50, max(1, (int) ($filtros['per_page'] ?? 25))),
        );

        $items = $pagina->items;
        $pacientes = $this->nombresPacientes($items);
        $sucursales = $this->nombresSucursales($items);
        $profesionales = $this->nombresProfesionales($items);
        $turnos = $this->turnosDe($items);

        $filas = [];

        foreach ($items as $item) {
            $filas[] = $this->fila($item, $turnos[$item->id] ?? [], $pacientes, $sucursales, $profesionales);
        }

        return [
            'data' => $filas,
            'meta' => [
                'current_page' => $pagina->page,
                'last_page' => $pagina->lastPage,
                'per_page' => $pagina->perPage,
                'total' => $pagina->total,
            ],
            'can' => [
                'ver' => $actor->can(DiagnosticosPermission::VER),
                'programar' => $actor->can(DiagnosticosPermission::PROGRAMAR),
                'exportar' => $actor->can(DiagnosticosPermission::EXPORTAR),
            ],
        ];
    }

    /**
     * @param  list<Turno>  $turnos
     * @param  array<int, string>  $pacientes
     * @param  array<int, string>  $sucursales
     * @param  array<int, string>  $profesionales
     * @return array<string, mixed>
     */
    private function fila(
        DiagnosticoAtencionResumen $item,
        array $turnos,
        array $pacientes,
        array $sucursales,
        array $profesionales,
    ): array {
        $resultado = $this->rule->evaluar(
            $item->requisitos,
            $this->turnos->paraRegla($turnos),
            $this->turnos->especialidadesPorProfesional($item),
        );

        $estado = $item->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value
            ? EstadoGeneralDiagnostico::CANCELADO
            : $resultado->estado;

        $hoy = $this->clock->hoy();
        $horaActual = $this->clock->horaActual();
        $turnosData = [];

        foreach ($turnos as $turno) {
            $existente = TurnoExistente::fromTurno($turno);
            $turnosData[] = [
                'id' => (int) $turno->id,
                'profesional_id' => $turno->profesional_id === null ? null : (int) $turno->profesional_id,
                'profesional_nombre' => $turno->profesional_id === null
                    ? 'Sin asignar'
                    : ($profesionales[(int) $turno->profesional_id] ?? 'No disponible'),
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
                'estado' => $turno->estado->value,
                'estado_label' => $turno->estado->label(),
                'es_futuro' => $this->futuro->esFuturo($existente, $hoy, $horaActual),
            ];
        }

        $profesionalesData = [];

        foreach ($item->profesionales as $profesional) {
            $id = (int) $profesional['id'];
            $turno = $this->turnoActual($turnosData, $id);
            $profesionalesData[] = [
                'id' => $id,
                'nombre_completo' => $profesionales[$id] ?? (string) $profesional['nombre_completo'],
                'turno' => $turno,
            ];
        }

        return [
            'id' => $item->id,
            'paciente' => [
                'id' => $item->pacienteId,
                'nombre_completo' => $pacientes[$item->pacienteId] ?? 'No disponible',
            ],
            'sucursal' => [
                'id' => $item->sucursalId,
                'nombre' => $sucursales[$item->sucursalId] ?? 'No disponible',
            ],
            'tipo_diagnostico' => [
                'id' => $item->tipoDiagnosticoId,
                'nombre' => $item->tipoDiagnosticoNombre,
            ],
            'fecha_inicio' => $item->fechaInicio,
            'fecha_fin' => $item->fechaFin,
            'estado' => $item->estado,
            'estado_label' => $item->estado === 'activa' ? 'Activa' : 'Retirada',
            'estado_general' => $estado->value,
            'estado_general_label' => $estado->label(),
            'profesionales' => $profesionalesData,
            'turnos' => $turnosData,
            'puestos' => $resultado->puestos,
            'faltantes' => $resultado->faltantes,
        ];
    }

    /**
     * @param  list<DiagnosticoAtencionResumen>  $items
     * @return array<int, string>
     */
    private function nombresPacientes(array $items): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (DiagnosticoAtencionResumen $item): int => $item->pacienteId,
            $items,
        )));

        if ($ids === []) {
            return [];
        }

        try {
            return $this->pacientes->nombres($ids);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  list<DiagnosticoAtencionResumen>  $items
     * @return array<int, string>
     */
    private function nombresSucursales(array $items): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (DiagnosticoAtencionResumen $item): int => $item->sucursalId,
            $items,
        )));

        if ($ids === []) {
            return [];
        }

        try {
            $resumenes = $this->sucursales->resumenes($ids);
        } catch (Throwable) {
            return [];
        }

        $nombres = [];

        foreach ($resumenes as $resumen) {
            /** @var BranchResumen $resumen */
            $nombres[$resumen->id] = $resumen->nombre;
        }

        return $nombres;
    }

    /**
     * @param  list<DiagnosticoAtencionResumen>  $items
     * @return array<int, string>
     */
    private function nombresProfesionales(array $items): array
    {
        $ids = [];

        foreach ($items as $item) {
            foreach ($item->profesionales as $profesional) {
                $ids[] = (int) $profesional['id'];
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        try {
            return $this->profesionales->nombres($ids);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  list<DiagnosticoAtencionResumen>  $items
     * @return array<int, list<Turno>>
     */
    private function turnosDe(array $items): array
    {
        $ids = array_map(
            static fn (DiagnosticoAtencionResumen $item): int => $item->id,
            $items,
        );

        if ($ids === []) {
            return [];
        }

        $agrupados = [];

        foreach (Turno::query()
            ->whereIn('atencion_diagnostico_id', $ids)
            ->ordenado()
            ->get() as $turno) {
            $agrupados[(int) $turno->atencion_diagnostico_id][] = $turno;
        }

        return $agrupados;
    }

    /**
     * @param  list<array<string, mixed>>  $turnos
     * @return array<string, mixed>|null
     */
    private function turnoActual(array $turnos, int $profesionalId): ?array
    {
        $falto = null;

        foreach ($turnos as $turno) {
            if ((int) $turno['profesional_id'] !== $profesionalId) {
                continue;
            }

            if (in_array($turno['estado'], ['programado', 'asistio'], true)) {
                return $turno;
            }

            if ($turno['estado'] === 'falto' && $falto === null) {
                $falto = $turno;
            }
        }

        return $falto;
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
