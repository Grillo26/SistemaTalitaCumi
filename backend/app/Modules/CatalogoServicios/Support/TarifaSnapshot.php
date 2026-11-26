<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\Tarifa;

final class TarifaSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Tarifa $tarifa): array
    {
        $tarifa->loadMissing(['servicio', 'categoria', 'sucursal']);

        return [
            'id' => $tarifa->id,
            'servicio_id' => $tarifa->servicio_id,
            'servicio_nombre' => $tarifa->servicio?->nombre,
            'categoria_tarifa_id' => $tarifa->categoria_tarifa_id,
            'categoria_nombre' => $tarifa->categoria?->nombre,
            'sucursal_id' => $tarifa->sucursal_id,
            'sucursal_nombre' => $tarifa->sucursal?->nombre,
            'monto' => (string) $tarifa->monto,
            'vigente_desde' => $tarifa->vigente_desde?->toDateString(),
            'vigente_hasta' => $tarifa->vigente_hasta?->toDateString(),
            'activo' => $tarifa->activo,
            'version' => $tarifa->version,
        ];
    }
}
