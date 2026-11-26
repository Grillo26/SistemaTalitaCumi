<?php

namespace App\Modules\CatalogoServicios\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServicioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area' => $this->area === null ? null : [
                'id' => $this->area->id,
                'nombre' => $this->area->nombre,
                'color' => $this->area->color,
                'activo' => (bool) $this->area->activo,
            ],
            'tipo_servicio' => $this->tipoServicio === null ? null : [
                'id' => $this->tipoServicio->id,
                'nombre' => $this->tipoServicio->nombre,
                'codigo' => $this->tipoServicio->codigo,
                'activo' => (bool) $this->tipoServicio->activo,
            ],
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'duracion_minutos' => (int) $this->duracion_minutos,
            'activo' => (bool) $this->activo,
            'estado' => $this->activo ? 'activo' : 'inactivo',
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
