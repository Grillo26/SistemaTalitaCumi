<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * [Agenda Semanal] RF-008.
 */
final class TurnoDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
