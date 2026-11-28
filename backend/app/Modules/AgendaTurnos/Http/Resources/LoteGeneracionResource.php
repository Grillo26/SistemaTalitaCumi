<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use App\Modules\AgendaTurnos\Support\LoteResultado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resultado de una generación o regeneración.
 *
 * @mixin LoteResultado
 */
final class LoteGeneracionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LoteResultado $resultado */
        $resultado = $this->resource;

        return $resultado->toArray();
    }
}
