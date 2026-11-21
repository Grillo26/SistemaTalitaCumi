<?php

namespace App\Modules\Profesionales\Support;

use App\Modules\Profesionales\Models\ProfesionalSucursal;

/**
 * Snapshot seguro de la relación profesional–sucursal para bitácora.
 */
final class ProfesionalSucursalSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(ProfesionalSucursal $relacion): array
    {
        return [
            'id' => $relacion->id,
            'profesional_id' => $relacion->profesional_id,
            'sucursal_id' => $relacion->sucursal_id,
            'activo' => (bool) $relacion->activo,
            'motivo' => $relacion->motivo,
            'version' => $relacion->version,
            'updated_at' => $relacion->updated_at?->toIso8601String(),
        ];
    }
}
