<?php

namespace App\Modules\Profesionales\Support;

use App\Modules\Profesionales\Models\Especialidad;

/**
 * Snapshot seguro de la especialidad para bitácora.
 */
final class EspecialidadSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Especialidad $especialidad): array
    {
        return [
            'id' => $especialidad->id,
            'nombre' => $especialidad->nombre,
            'descripcion' => $especialidad->descripcion,
            'activo' => (bool) $especialidad->activo,
            'motivo_estado' => $especialidad->motivo_estado,
            'estado_cambiado_en' => $especialidad->estado_cambiado_en?->toIso8601String(),
            'version' => $especialidad->version,
            'updated_at' => $especialidad->updated_at?->toIso8601String(),
        ];
    }
}
