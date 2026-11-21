<?php

namespace App\Modules\Organizacion\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Área de solo lectura. No expone el color: la agenda lo consume desde el
 * módulo 05.
 */
final class AreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'activo' => $this->activo,
        ];
    }
}
