<?php

namespace App\Modules\CatalogoServicios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TipoServicioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'activo' => (bool) $this->activo,
            'estado' => $this->activo ? 'activo' : 'inactivo',
            'servicios_total_count' => isset($this->servicios_total_count)
                ? (int) $this->servicios_total_count
                : null,
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
