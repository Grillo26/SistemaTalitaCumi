<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turno de un origen, sin datos clínicos ni de contacto (RNF-005).
 */
final class TurnoOrigenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
