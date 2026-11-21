<?php

namespace App\Modules\Organizacion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ambiente de atención individual (RF-006/RF-009).
 */
final class AmbienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sucursal' => $this->sucursal?->only(['id', 'nombre']),
            'nombre' => $this->nombre,
            'uso' => $this->uso->value,
            'uso_label' => $this->uso->label(),
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
            'estado' => $this->activo ? 'activa' : 'inactiva',
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
