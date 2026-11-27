<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Modules\InscripcionesAtenciones\Support\DiagnosticoCatalogoGuard;
use App\Modules\Profesionales\Contracts\ProfessionalDiagnosticRequirementsContract;

/**
 * Requisitos de especialidades de un tipo de diagnóstico (RF-003/RF-004).
 *
 * Los requisitos vienen del contrato real del Catálogo; el nombre y estado
 * de la especialidad se resuelven con una lectura puntual del módulo 03.
 */
final class GetRequisitosDiagnosticoQuery
{
    public function __construct(
        private readonly DiagnosticoCatalogoGuard $catalogo,
        private readonly ProfessionalDiagnosticRequirementsContract $requisitos,
    ) {}

    /**
     * @return array{
     *     tipo: array<string, mixed>,
     *     especialidades: list<array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int, activo: bool}>
     * }
     */
    public function __invoke(int $tipoDiagnosticoId): array
    {
        $tipo = $this->catalogo->resumen($tipoDiagnosticoId);
        $requisitos = $this->requisitos->especialidadesRequeridas($tipoDiagnosticoId);

        $ids = array_map(
            static fn (array $requisito): int => (int) $requisito['especialidad_id'],
            $requisitos,
        );
        $nombres = $this->catalogo->especialidadesPorIds($ids);

        $especialidades = array_map(
            static function (array $requisito) use ($nombres): array {
                $id = (int) $requisito['especialidad_id'];
                $meta = $nombres[$id] ?? null;

                return [
                    'especialidad_id' => $id,
                    'especialidad_nombre' => $meta['nombre'] ?? 'Especialidad',
                    'cantidad_minima' => (int) $requisito['cantidad_minima'],
                    'activo' => $meta['activo'] ?? false,
                ];
            },
            $requisitos,
        );

        return [
            'tipo' => $tipo,
            'especialidades' => $especialidades,
        ];
    }
}
