<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\Servicio;

/**
 * Snapshot de servicio para eventos y bitácora.
 */
final class ServicioSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Servicio $servicio): array
    {
        return [
            'id' => $servicio->id,
            'area_id' => $servicio->area_id,
            'area_nombre' => $servicio->area?->nombre,
            'tipo_servicio_id' => $servicio->tipo_servicio_id,
            'tipo_servicio_nombre' => $servicio->tipoServicio?->nombre,
            'nombre' => $servicio->nombre,
            'descripcion' => $servicio->descripcion,
            'duracion_minutos' => $servicio->duracion_minutos,
            'activo' => $servicio->activo,
            'version' => $servicio->version,
        ];
    }
}
