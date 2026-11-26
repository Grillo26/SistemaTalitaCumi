<?php

namespace App\Modules\CatalogoServicios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TipoDiagnosticoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $especialidades = $this->especialidades
            ->sortBy('nombre')
            ->values();

        return [
            'id' => $this->id,
            'servicio' => $this->servicio === null ? null : [
                'id' => $this->servicio->id,
                'nombre' => $this->servicio->nombre,
                'codigo' => $this->servicio->tipoServicio?->codigo,
                'activo' => (bool) $this->servicio->activo,
                'area' => $this->servicio->area === null ? null : [
                    'id' => $this->servicio->area->id,
                    'nombre' => $this->servicio->area->nombre,
                    'color' => $this->servicio->area->color,
                ],
            ],
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'dias_estimados' => (int) $this->dias_estimados,
            'activo' => (bool) $this->activo,
            'estado' => $this->activo ? 'activo' : 'inactivo',
            'especialidades' => $especialidades
                ->map(static fn ($especialidad): array => [
                    'especialidad_id' => $especialidad->id,
                    'nombre' => $especialidad->nombre,
                    'cantidad_minima' => (int) $especialidad->pivot->cantidad_minima,
                    'activo' => (bool) $especialidad->activo,
                ])
                ->all(),
            'especialidades_count' => $especialidades->count(),
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
