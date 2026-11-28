<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use App\Modules\AgendaTurnos\Models\Feriado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feriado
 */
final class FeriadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Feriado $feriado */
        $feriado = $this->resource;

        return [
            'id' => (int) $feriado->id,
            'fecha' => $feriado->fecha->toDateString(),
            'descripcion' => (string) $feriado->descripcion,
            'sucursal_id' => $feriado->sucursal_id === null ? null : (int) $feriado->sucursal_id,
            'activo' => (bool) $feriado->activo,
            'created_at' => $feriado->created_at?->toIso8601String(),
            'updated_at' => $feriado->updated_at?->toIso8601String(),
        ];
    }
}
