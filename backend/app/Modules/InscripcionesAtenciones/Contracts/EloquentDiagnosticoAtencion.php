<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Support\AtencionQueryJoins;

/**
 * Implementación Eloquent del contrato de diagnóstico para 07.05.
 *
 * Solo expone datos operativos: paciente/sucursal/servicio por id, período,
 * estado y requisitos. Los nombres de paciente, sucursal y profesionales se
 * resuelven en Agenda con los contratos de cada módulo.
 */
final class EloquentDiagnosticoAtencion implements DiagnosticoAtencionContract
{
    private const MAX_PER_PAGE = 5000;

    public function buscar(
        array $sucursalIds,
        ?string $desde,
        ?string $hasta,
        ?int $tipoDiagnosticoId,
        ?string $estadoGeneral,
        ?int $profesionalId,
        int $page,
        int $perPage,
    ): DiagnosticoAtencionPagina {
        $sucursalIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $sucursalIds,
        )));

        $page = max(1, $page);
        $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));

        if ($sucursalIds === []) {
            return new DiagnosticoAtencionPagina([], 0, $page, $perPage, 1);
        }

        $query = AtencionQueryJoins::diagnosticos(AtencionDiagnostico::query())
            ->with([
                'profesionales:id,nombre_completo',
                'profesionales.especialidades:id,nombre',
                'requisitos',
            ])
            ->whereIn('atencion_diagnostico.sucursal_id', $sucursalIds);

        if ($desde !== null && $hasta !== null) {
            $query->whereDate('atencion_diagnostico.fecha_inicio', '<=', $hasta)
                ->whereDate('atencion_diagnostico.fecha_fin', '>=', $desde);
        }

        if ($tipoDiagnosticoId !== null) {
            $query->where('atencion_diagnostico.tipo_diagnostico_id', $tipoDiagnosticoId);
        }

        if ($estadoGeneral !== null) {
            $query->where('atencion_diagnostico.estado_general', $estadoGeneral);
        }

        if ($profesionalId !== null) {
            $query->whereHas(
                'profesionales',
                static fn ($profesionales) => $profesionales->where('profesional.id', $profesionalId),
            );
        }

        $paginator = $query
            ->orderBy('atencion_diagnostico.fecha_inicio')
            ->orderBy('atencion_diagnostico.id')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = [];

        foreach ($paginator->items() as $diagnostico) {
            $items[] = $this->resumenDesdeModelo($diagnostico);
        }

        return new DiagnosticoAtencionPagina(
            items: $items,
            total: (int) $paginator->total(),
            page: (int) $paginator->currentPage(),
            perPage: (int) $paginator->perPage(),
            lastPage: (int) $paginator->lastPage(),
        );
    }

    public function resumen(int $diagnosticoId): ?DiagnosticoAtencionResumen
    {
        $diagnostico = $this->queryResumen($diagnosticoId);

        return $diagnostico === null ? null : $this->resumenDesdeModelo($diagnostico);
    }

    public function resumenParaActualizar(int $diagnosticoId): ?DiagnosticoAtencionResumen
    {
        $diagnostico = $this->queryResumen($diagnosticoId, bloquear: true);

        return $diagnostico === null ? null : $this->resumenDesdeModelo($diagnostico);
    }

    public function actualizarEstadoGeneral(int $diagnosticoId, string $estadoGeneral): bool
    {
        $actual = AtencionDiagnostico::query()
            ->whereKey($diagnosticoId)
            ->lockForUpdate()
            ->value('estado_general');

        if ($actual === null || $actual === $estadoGeneral) {
            return false;
        }

        return AtencionDiagnostico::query()
            ->whereKey($diagnosticoId)
            ->update([
                'estado_general' => $estadoGeneral,
                'updated_at' => now(),
            ]) > 0;
    }

    public function tiposDiagnostico(): array
    {
        return TipoDiagnostico::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(static fn (TipoDiagnostico $tipo): array => [
                'id' => (int) $tipo->id,
                'nombre' => (string) $tipo->nombre,
            ])
            ->values()
            ->all();
    }

    private function queryResumen(int $diagnosticoId, bool $bloquear = false): ?AtencionDiagnostico
    {
        if ($bloquear) {
            // FOR UPDATE no admite el lado nullable de los LEFT JOIN del
            // resumen. Se bloquea primero la fila base y luego se lee el
            // resumen completo dentro de la misma transacción.
            AtencionDiagnostico::query()
                ->whereKey($diagnosticoId)
                ->lockForUpdate()
                ->first();
        }

        $query = AtencionQueryJoins::diagnosticos(AtencionDiagnostico::query())
            ->with([
                'profesionales:id,nombre_completo',
                'profesionales.especialidades:id,nombre',
                'requisitos',
            ])
            ->where('atencion_diagnostico.id', $diagnosticoId);

        /** @var AtencionDiagnostico|null $diagnostico */
        return $query->first();
    }

    private function resumenDesdeModelo(AtencionDiagnostico $diagnostico): DiagnosticoAtencionResumen
    {
        return new DiagnosticoAtencionResumen(
            id: (int) $diagnostico->id,
            pacienteId: (int) $diagnostico->paciente_id,
            sucursalId: (int) $diagnostico->sucursal_id,
            servicioId: (int) $diagnostico->servicio_id,
            servicioNombre: (string) ($diagnostico->servicio_nombre ?? ''),
            tipoDiagnosticoId: (int) $diagnostico->tipo_diagnostico_id,
            tipoDiagnosticoNombre: (string) ($diagnostico->tipo_diagnostico_nombre ?? ''),
            fechaInicio: $diagnostico->fecha_inicio->toDateString(),
            fechaFin: $diagnostico->fecha_fin->toDateString(),
            estado: $diagnostico->estado->value,
            estadoGeneral: (string) ($diagnostico->estado_general ?? 'incompleto'),
            profesionales: $diagnostico->profesionales
                ->map(static fn ($profesional): array => [
                    'id' => (int) $profesional->id,
                    'nombre_completo' => (string) $profesional->nombre_completo,
                    'especialidades' => $profesional->especialidades
                        ->map(static fn ($especialidad): array => [
                            'id' => (int) $especialidad->id,
                            'nombre' => (string) $especialidad->nombre,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            requisitos: $diagnostico->requisitos
                ->sortBy('especialidad_nombre')
                ->map(static fn ($requisito): array => [
                    'especialidad_id' => (int) $requisito->especialidad_id,
                    'especialidad_nombre' => (string) $requisito->especialidad_nombre,
                    'cantidad_minima' => (int) $requisito->cantidad_minima,
                ])
                ->values()
                ->all(),
        );
    }
}
