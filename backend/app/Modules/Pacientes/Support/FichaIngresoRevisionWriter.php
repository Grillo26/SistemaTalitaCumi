<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoRevision;

/**
 * Escribe una revisión completa de la ficha (04.03/RF-005).
 *
 * La revisión es inmutable y contiene el snapshot con datos sensibles. La
 * bitácora solo recibe los metadatos de FichaIngresoSnapshot.
 */
final class FichaIngresoRevisionWriter
{
    /**
     * @param  list<string>|null  $changedFields
     */
    public function record(
        FichaIngreso $ficha,
        string $accion,
        int $usuarioId,
        ?array $changedFields = null,
    ): FichaIngresoRevision {
        return FichaIngresoRevision::query()->create([
            'ficha_ingreso_id' => $ficha->id,
            'version' => $ficha->version,
            'accion' => $accion,
            'snapshot' => $this->snapshot($ficha),
            'campos_modificados' => $changedFields,
            'usuario_id' => $usuarioId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(FichaIngreso $ficha): array
    {
        return [
            'id' => (int) $ficha->id,
            'paciente_id' => (int) $ficha->paciente_id,
            'sucursal_ingreso_id' => (int) $ficha->sucursal_ingreso_id,
            'tutor_principal_id' => $ficha->tutor_principal_id === null
                ? null
                : (int) $ficha->tutor_principal_id,
            'motivo_consulta_inicial' => $ficha->motivo_consulta_inicial,
            'fecha_ingreso' => $ficha->fecha_ingreso->toDateString(),
            'composicion_familiar' => $ficha->composicion_familiar,
            'antecedentes_familiares' => $ficha->antecedentes_familiares,
            'observaciones' => $ficha->observaciones,
            'derivado_por' => $ficha->derivado_por,
            'derivado_por_institucion' => $ficha->derivado_por_institucion,
            'derivado_por_contacto' => $ficha->derivado_por_contacto,
            'estado' => $ficha->estado->value,
            'estado_anterior' => $ficha->estado_anterior,
            'version' => (int) $ficha->version,
            'usuario_registro_id' => (int) $ficha->usuario_registro_id,
            'completada_en' => $ficha->completada_en?->toIso8601String(),
            'completada_por_id' => $ficha->completada_por_id,
            'archivada_en' => $ficha->archivada_en?->toIso8601String(),
            'archivada_por_id' => $ficha->archivada_por_id,
            'motivo_archivado' => $ficha->motivo_archivado,
            'created_at' => $ficha->created_at?->toIso8601String(),
            'updated_at' => $ficha->updated_at?->toIso8601String(),
        ];
    }
}
