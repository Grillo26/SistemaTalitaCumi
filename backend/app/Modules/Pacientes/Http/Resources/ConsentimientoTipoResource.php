<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Modules\Pacientes\Models\ConsentimientoTipo;

/**
 * Tipo de consentimiento (04.03 Fase B).
 */
final class ConsentimientoTipoResource
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(?ConsentimientoTipo $tipo): ?array
    {
        if ($tipo === null) {
            return null;
        }

        return [
            'id' => (int) $tipo->id,
            'codigo' => $tipo->codigo,
            'nombre' => $tipo->nombre,
            'descripcion' => $tipo->descripcion,
            'orden' => (int) $tipo->orden,
            'activo' => (bool) $tipo->activo,
            'version' => (int) $tipo->version,
        ];
    }
}
