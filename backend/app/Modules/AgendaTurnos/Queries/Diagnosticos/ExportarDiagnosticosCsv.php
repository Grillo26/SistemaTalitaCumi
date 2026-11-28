<?php

namespace App\Modules\AgendaTurnos\Queries\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use App\Modules\AgendaTurnos\Support\DiagnosticoTurnosResolver;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * 07.05/RF-008 / 07.05/FP-04 / 07.05/CU-03.
 *
 * CSV compatible con Excel (BOM UTF-8, punto y coma, escape de fórmulas).
 * Una fila por profesional del diagnóstico. La exportación queda auditada con
 * filtros seguros y cantidad; nunca se registran nombres ni datos clínicos.
 */
final class ExportarDiagnosticosCsv
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly DiagnosticoTurnosResolver $turnos,
        private readonly EstadoGeneralDiagnosticoRule $rule,
        private readonly PatientDirectoryContract $pacientes,
        private readonly BranchDirectoryContract $sucursales,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly AuditRecorderContract $auditoria,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor, ?string $ip = null): StreamedResponse
    {
        $accesibles = $this->access->accesibles($actor);
        $sucursalId = isset($filtros['sucursal_id']) ? (int) $filtros['sucursal_id'] : null;

        if ($sucursalId !== null && ! in_array($sucursalId, $accesibles, true)) {
            throw new BranchAccessDeniedException;
        }

        $pagina = $this->diagnosticos->buscar(
            sucursalIds: $sucursalId === null ? $accesibles : [$sucursalId],
            desde: (string) $filtros['desde'],
            hasta: (string) $filtros['hasta'],
            tipoDiagnosticoId: isset($filtros['tipo_diagnostico_id'])
                ? (int) $filtros['tipo_diagnostico_id']
                : null,
            estadoGeneral: isset($filtros['estado_general']) && $filtros['estado_general'] !== ''
                ? (string) $filtros['estado_general']
                : null,
            profesionalId: isset($filtros['profesional_id'])
                ? (int) $filtros['profesional_id']
                : null,
            page: 1,
            perPage: DiagnosticosModule::MAX_FILAS_EXPORTACION,
        );

        if ($pagina->total > DiagnosticosModule::MAX_FILAS_EXPORTACION) {
            throw ValidationException::withMessages([
                'reporte' => ['El reporte supera el límite de '.DiagnosticosModule::MAX_FILAS_EXPORTACION.' diagnósticos. Acotá el período o los filtros.'],
            ]);
        }

        $items = $pagina->items;
        $pacientes = $this->nombresPacientes($items);
        $sucursales = $this->nombresSucursales($items);
        $profesionales = $this->nombresProfesionales($items);
        $turnos = $this->turnosDe($items);
        $filas = [];

        foreach ($items as $item) {
            $turnosItem = $turnos[$item->id] ?? [];
            $resultado = $this->rule->evaluar(
                $item->requisitos,
                $this->turnos->paraRegla($turnosItem),
                $this->turnos->especialidadesPorProfesional($item),
            );
            $estado = $item->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value
                ? EstadoGeneralDiagnostico::CANCELADO
                : $resultado->estado;

            if ($item->profesionales === []) {
                $filas[] = [
                    $sucursales[$item->sucursalId] ?? 'No disponible',
                    $pacientes[$item->pacienteId] ?? 'No disponible',
                    $item->tipoDiagnosticoNombre,
                    $item->fechaInicio,
                    $item->fechaFin,
                    $estado->label(),
                    'Sin profesional',
                    '',
                    '',
                    '',
                    'Sin turno',
                ];

                continue;
            }

            foreach ($item->profesionales as $profesional) {
                $id = (int) $profesional['id'];
                $turno = $this->turnoActual($turnosItem, $id);
                $especialidades = implode(', ', array_map(
                    static fn (array $especialidad): string => (string) $especialidad['nombre'],
                    $profesional['especialidades'],
                ));

                $filas[] = [
                    $sucursales[$item->sucursalId] ?? 'No disponible',
                    $pacientes[$item->pacienteId] ?? 'No disponible',
                    $item->tipoDiagnosticoNombre,
                    $item->fechaInicio,
                    $item->fechaFin,
                    $estado->label(),
                    $profesionales[$id] ?? (string) $profesional['nombre_completo'],
                    $especialidades,
                    $turno?->fecha->toDateString() ?? '',
                    $turno === null ? '' : (string) $turno->hora_inicio,
                    $turno?->estado->label() ?? 'Sin turno',
                ];
            }
        }

        $this->auditoria->registrarExportacion(
            recurso: DiagnosticosModule::RECURSO_EXPORTACION,
            filtros: $this->filtrosSeguros($filtros),
            cantidad: count($filas),
            sucursalId: $sucursalId,
            usuarioId: (int) $actor->id,
            ip: $ip,
        );

        $nombre = 'diagnosticos-'.$filtros['desde'].'-'.$filtros['hasta'].'.csv';

        return response()->streamDownload(function () use ($filas): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Sucursal',
                'Paciente',
                'Tipo de diagnóstico',
                'Período desde',
                'Período hasta',
                'Estado general',
                'Profesional',
                'Especialidades',
                'Fecha del turno',
                'Hora del turno',
                'Estado del turno',
            ], ';', '"', '');

            foreach ($filas as $fila) {
                fputcsv($handle, array_map([$this, 'safe'], $fila), ';', '"', '');
            }

            fclose($handle);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function safe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function filtrosSeguros(array $filtros): array
    {
        return [
            'desde' => $filtros['desde'] ?? null,
            'hasta' => $filtros['hasta'] ?? null,
            'sucursal_id' => $filtros['sucursal_id'] ?? null,
            'tipo_diagnostico_id' => $filtros['tipo_diagnostico_id'] ?? null,
            'estado_general' => $filtros['estado_general'] ?? null,
            'profesional_id' => $filtros['profesional_id'] ?? null,
        ];
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
     * @param  list<Turno>  $turnos
     */
    private function turnoActual(array $turnos, int $profesionalId): ?Turno
    {
        $falto = null;

        foreach ($turnos as $turno) {
            if ((int) $turno->profesional_id !== $profesionalId) {
                continue;
            }

            if (in_array($turno->estado->value, ['programado', 'asistio'], true)) {
                return $turno;
            }

            if ($turno->estado->value === 'falto' && $falto === null) {
                $falto = $turno;
            }
        }

        return $falto;
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
}
