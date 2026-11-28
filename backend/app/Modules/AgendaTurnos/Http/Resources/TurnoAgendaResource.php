<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila operativa de un turno. No expone datos clínicos ni de contacto.
 */
final class TurnoAgendaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
