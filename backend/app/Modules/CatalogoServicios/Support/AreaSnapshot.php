<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\Area;

/**
 * Snapshot de área para eventos y bitácora.
 */
final class AreaSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Area $area): array
    {
        return [
            'id' => $area->id,
            'nombre' => $area->nombre,
            'descripcion' => $area->descripcion,
            'color' => $area->color,
            'activo' => $area->activo,
            'version' => $area->version,
        ];
    }
}
