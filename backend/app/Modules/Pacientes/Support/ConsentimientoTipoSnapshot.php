<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\ConsentimientoTipo;

/**
 * Snapshot de catálogo de consentimientos para bitácora (04.03 Fase B).
 */
final class ConsentimientoTipoSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(ConsentimientoTipo $tipo, ?array $changedFields = null): array
    {
        return [
            'id' => (int) $tipo->id,
            'codigo' => $tipo->codigo,
            'nombre' => $tipo->nombre,
            'orden' => (int) $tipo->orden,
            'activo' => (bool) $tipo->activo,
            'version' => (int) $tipo->version,
            'campos_modificados' => $changedFields,
        ];
    }
}
