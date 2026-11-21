<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use App\Modules\Pacientes\Support\FichaIngresoPermission;

/**
 * Observación fechada (04.03 Fase C, RF-006).
 *
 * El texto es sensible y solo se envía con `ficha_ingreso.ver_sensibles`.
 * La referencia se expone por id y fecha, sin copiar su texto.
 */
final class ObservacionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $actor, FichaIngresoObservacion $observacion): array
    {
        $observacion->loadMissing(['usuario', 'referencia']);
        $fullSensitive = $actor->can(FichaIngresoPermission::VIEW_SENSITIVE);

        return [
            'id' => (int) $observacion->id,
            'ficha_ingreso_id' => (int) $observacion->ficha_ingreso_id,
            'fecha' => $observacion->fecha->toDateString(),
            'texto' => $fullSensitive ? $observacion->texto : null,
            'texto_protegido' => ! $fullSensitive,
            'observacion_referencia_id' => $observacion->observacion_referencia_id === null
                ? null
                : (int) $observacion->observacion_referencia_id,
            'referencia' => $observacion->referencia === null
                ? null
                : [
                    'id' => (int) $observacion->referencia->id,
                    'fecha' => $observacion->referencia->fecha->toDateString(),
                ],
            'usuario_id' => (int) $observacion->usuario_id,
            'usuario_nombre' => $observacion->usuario?->name,
            'created_at' => $observacion->created_at?->toIso8601String(),
        ];
    }
}
