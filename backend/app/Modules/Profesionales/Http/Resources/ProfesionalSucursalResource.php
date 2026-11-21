<?php

namespace App\Modules\Profesionales\Http\Resources;

use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de la relación profesional–sucursal (03/RF-E06).
 */
final class ProfesionalSucursalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProfesionalSucursal $relacion */
        $relacion = $this->resource;

        return [
            'id' => $relacion->id,
            'profesional_id' => $relacion->profesional_id,
            'sucursal_id' => $relacion->sucursal_id,
            'sucursal' => $relacion->sucursal === null ? null : [
                'id' => $relacion->sucursal->id,
                'codigo' => $relacion->sucursal->codigo,
                'nombre' => $relacion->sucursal->nombre,
                'activo' => (bool) $relacion->sucursal->activo,
            ],
            'activo' => (bool) $relacion->activo,
            'motivo' => $relacion->motivo,
            'version' => $relacion->version,
            'created_at' => $relacion->created_at?->toIso8601String(),
            'updated_at' => $relacion->updated_at?->toIso8601String(),
        ];
    }
}
