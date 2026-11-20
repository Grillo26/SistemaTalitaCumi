<?php

namespace App\Modules\Organizacion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Entrada de historial de un parámetro (FP-04 / RF-013).
 */
final class ParameterHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accion' => $this->accion,
            'motivo' => $this->motivo,
            'usuario' => $this->usuario === null ? null : [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->name,
            ],
            'datos_anteriores' => $this->datos_anteriores,
            'datos_nuevos' => $this->datos_nuevos,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
