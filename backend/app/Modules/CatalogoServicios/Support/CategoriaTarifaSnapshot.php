<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;

final class CategoriaTarifaSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(CategoriaTarifa $categoria): array
    {
        return [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'descripcion' => $categoria->descripcion,
            'activo' => $categoria->activo,
            'version' => $categoria->version,
        ];
    }
}
