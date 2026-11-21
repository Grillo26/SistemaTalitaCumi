<?php

namespace App\Modules\Organizacion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sala grupal (RF-005/RF-009).
 */
final class SalaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sucursal' => $this->sucursal?->only(['id', 'nombre']),
            'area' => $this->area?->only(['id', 'nombre']),
            'tipo_sala' => $this->tipoSala?->only(['id', 'nombre']),
            'turno' => $this->turnoCatalogo?->only(['id', 'nombre']),
            'nombre' => $this->nombre,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
            'estado' => $this->activo ? 'activa' : 'inactiva',
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
