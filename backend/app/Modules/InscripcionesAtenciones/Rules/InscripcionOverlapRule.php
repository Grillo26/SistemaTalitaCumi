<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDuplicadaException;

/**
 * RF-003 / RN-003: un paciente puede tener varias inscripciones activas,
 * pero no dos en la misma sala con períodos solapados.
 *
 * La consulta la hace la Action; esta regla solo evalúa datos puros.
 */
final class InscripcionOverlapRule
{
    /**
     * @param  list<array{id: int, fecha_inicio: string, fecha_fin: string}>  $periodos
     */
    public static function assertSinSolapamiento(
        array $periodos,
        string $fechaInicio,
        string $fechaFin,
        ?int $excluirId = null,
    ): void {
        foreach ($periodos as $periodo) {
            if ($excluirId !== null && (int) $periodo['id'] === $excluirId) {
                continue;
            }

            if ($fechaInicio <= $periodo['fecha_fin'] && $fechaFin >= $periodo['fecha_inicio']) {
                throw new InscripcionDuplicadaException([
                    'inscripcion_id' => (int) $periodo['id'],
                    'fecha_inicio' => $periodo['fecha_inicio'],
                    'fecha_fin' => $periodo['fecha_fin'],
                    'fecha_inicio_solicitada' => $fechaInicio,
                    'fecha_fin_solicitada' => $fechaFin,
                ]);
            }
        }
    }
}
