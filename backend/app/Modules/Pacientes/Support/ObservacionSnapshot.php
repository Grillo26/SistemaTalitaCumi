<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\FichaIngresoObservacion;

/**
 * Snapshot no sensible de una observación para bitácora (04.03 Fase C).
 *
 * Nunca copia el texto de la observación.
 */
final class ObservacionSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(FichaIngresoObservacion $observacion): array
    {
        return [
            'id' => (int) $observacion->id,
            'ficha_ingreso_id' => (int) $observacion->ficha_ingreso_id,
            'fecha' => $observacion->fecha->toDateString(),
            'observacion_referencia_id' => $observacion->observacion_referencia_id === null
                ? null
                : (int) $observacion->observacion_referencia_id,
            'usuario_id' => (int) $observacion->usuario_id,
            'tiene_texto' => filled($observacion->texto),
            'created_at' => $observacion->created_at?->toIso8601String(),
        ];
    }
}
