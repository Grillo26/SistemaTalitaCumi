<?php

namespace App\Modules\CatalogoServicios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoriaTarifaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,
            'estado' => $this->activo ? 'activo' : 'inactivo',
            'tarifas_vigentes_count' => isset($this->tarifas_vigentes_count)
                ? (int) $this->tarifas_vigentes_count
                : null,
            'tarifas_total_count' => isset($this->tarifas_total_count)
                ? (int) $this->tarifas_total_count
                : null,
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
