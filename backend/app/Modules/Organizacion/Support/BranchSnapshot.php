<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Models\Branch;

final class BranchSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'codigo' => $branch->codigo,
            'nombre' => $branch->nombre,
            'direccion' => $branch->direccion,
            'telefono' => $branch->telefono,
            'email' => $branch->email,
            'activo' => $branch->activo,
            'version' => $branch->version,
            'updated_at' => $branch->updated_at?->toIso8601String(),
        ];
    }
}
