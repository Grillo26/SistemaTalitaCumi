<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Snapshot de ficha para bitácora (04.03/RF-011, 04.03/RNF-012).
 *
 * Nunca copia contenido de salud, contexto familiar ni datos del derivante.
 */
final class FichaIngresoSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(FichaIngreso $ficha, ?int $revisionId = null): array
    {
        return [
            'id' => (int) $ficha->id,
            'paciente_id' => (int) $ficha->paciente_id,
            'sucursal_ingreso_id' => (int) $ficha->sucursal_ingreso_id,
            'tutor_principal_id' => $ficha->tutor_principal_id === null
                ? null
                : (int) $ficha->tutor_principal_id,
            'fecha_ingreso' => $ficha->fecha_ingreso->toDateString(),
            'estado' => $ficha->estado->value,
            'estado_anterior' => $ficha->estado_anterior,
            'version' => (int) $ficha->version,
            'tiene_motivo_consulta' => filled($ficha->motivo_consulta_inicial),
            'tiene_composicion_familiar' => filled($ficha->composicion_familiar),
            'tiene_antecedentes' => filled($ficha->antecedentes_familiares),
            'tiene_observaciones' => filled($ficha->observaciones),
            'tiene_derivante' => filled($ficha->derivado_por),
            'tiene_institucion_derivante' => filled($ficha->derivado_por_institucion),
            'tiene_contacto_derivante' => filled($ficha->derivado_por_contacto),
            'revision_id' => $revisionId,
            'updated_at' => $ficha->updated_at?->toIso8601String(),
        ];
    }
}
