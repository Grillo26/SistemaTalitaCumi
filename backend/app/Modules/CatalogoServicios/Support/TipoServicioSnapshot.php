<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\TipoServicio;

/**
 * Snapshot de tipo de servicio para eventos y bitácora.
 */
final class TipoServicioSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(TipoServicio $tipo): array
    {
        return [
            'id' => $tipo->id,
            'nombre' => $tipo->nombre,
            'activo' => $tipo->activo,
            'version' => $tipo->version,
        ];
    }
}
