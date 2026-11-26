<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\TipoDiagnostico;

final class TipoDiagnosticoSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(TipoDiagnostico $tipo): array
    {
        $tipo->loadMissing(['servicio', 'especialidades']);

        return [
            'id' => $tipo->id,
            'servicio_id' => $tipo->servicio_id,
            'servicio_nombre' => $tipo->servicio?->nombre,
            'nombre' => $tipo->nombre,
            'descripcion' => $tipo->descripcion,
            'dias_estimados' => $tipo->dias_estimados,
            'activo' => $tipo->activo,
            'version' => $tipo->version,
            'especialidades' => $tipo->especialidades
                ->map(static fn ($especialidad): array => [
                    'especialidad_id' => $especialidad->id,
                    'nombre' => $especialidad->nombre,
                    'cantidad_minima' => (int) $especialidad->pivot->cantidad_minima,
                    'activo' => (bool) $especialidad->activo,
                ])
                ->values()
                ->all(),
        ];
    }
}
