<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

/**
 * Snapshot estable para bitácora y eventos.
 */
final class InscripcionSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(InscripcionSala $inscripcion): array
    {
        return [
            'id' => (int) $inscripcion->id,
            'paciente_id' => (int) $inscripcion->paciente_id,
            'tutor_responsable_id' => (int) $inscripcion->tutor_responsable_id,
            'sala_id' => (int) $inscripcion->sala_id,
            'sucursal_id' => (int) $inscripcion->sucursal_id,
            'servicio_id' => (int) $inscripcion->servicio_id,
            'tarifa_id' => (int) $inscripcion->tarifa_id,
            'categoria_tarifa_id' => (int) $inscripcion->categoria_tarifa_id,
            'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
            'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
            'monto_esperado' => (string) $inscripcion->monto_esperado,
            'estado' => $inscripcion->estado->value,
            'inscripcion_origen_id' => $inscripcion->inscripcion_origen_id === null
                ? null
                : (int) $inscripcion->inscripcion_origen_id,
            'observaciones' => $inscripcion->observaciones,
            'created_at' => $inscripcion->created_at?->toIso8601String(),
            'updated_at' => $inscripcion->updated_at?->toIso8601String(),
        ];
    }
}
