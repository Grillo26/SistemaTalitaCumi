<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * [Agenda Semanal] RF-001, RF-003, RF-004, RF-013, RF-014.
 */
final class AgendaSemanalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;
        $data['turnos'] = TurnoAgendaResource::collection($data['turnos'] ?? [])->resolve($request);

        return $data;
    }
}
