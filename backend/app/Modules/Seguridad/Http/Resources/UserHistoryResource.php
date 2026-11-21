<?php

namespace App\Modules\Seguridad\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Entrada de historial de usuario (02/RF-U12).
 */
final class UserHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accion' => $this->accion,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->usuario?->name,
            'datos_anteriores' => $this->datos_anteriores,
            'datos_nuevos' => $this->datos_nuevos,
            'motivo' => $this->motivo,
            'ip' => $this->ip,
            'modulo' => $this->modulo,
            'resultado' => $this->resultado,
            'critico' => (bool) $this->critico,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
