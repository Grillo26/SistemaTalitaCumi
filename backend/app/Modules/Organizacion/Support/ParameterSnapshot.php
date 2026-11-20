<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Models\Parametro;

/**
 * Snapshot de un parámetro para bitácora y eventos.
 */
final class ParameterSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Parametro $parametro): array
    {
        return [
            'id' => $parametro->id,
            'clave' => $parametro->clave,
            'categoria' => $parametro->categoria->value,
            'descripcion' => $parametro->descripcion,
            'tipo' => $parametro->tipo->value,
            'valor' => $parametro->valor,
            'valor_defecto' => $parametro->valor_defecto,
            'unidad' => $parametro->unidad,
            'minimo' => $parametro->minimo,
            'maximo' => $parametro->maximo,
            'sensible' => $parametro->sensible,
            'editable' => $parametro->editable,
            'visible_recepcion' => $parametro->visible_recepcion,
            'version' => $parametro->version,
            'updated_at' => $parametro->updated_at?->toIso8601String(),
        ];
    }
}
