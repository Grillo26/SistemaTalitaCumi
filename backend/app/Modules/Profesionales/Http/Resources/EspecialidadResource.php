<?php

namespace App\Modules\Profesionales\Http\Resources;

use App\Models\User;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Support\EspecialidadAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de la especialidad (03/RF-E03).
 */
final class EspecialidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public static function forActor(User $actor, Especialidad $especialidad, Request $request): array
    {
        $data = (new self($especialidad))->resolve($request);
        $data['can'] = EspecialidadAbilities::for($actor, $especialidad);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Especialidad $especialidad */
        $especialidad = $this->resource;

        return [
            'id' => $especialidad->id,
            'nombre' => $especialidad->nombre,
            'descripcion' => $especialidad->descripcion,
            'activo' => (bool) $especialidad->activo,
            'estado' => $especialidad->estado,
            'motivo_estado' => $especialidad->motivo_estado,
            'estado_cambiado_en' => $especialidad->estado_cambiado_en?->toIso8601String(),
            'profesionales_activos_count' => (int) ($especialidad->profesionales_activos_count ?? 0),
            'version' => $especialidad->version,
            'created_at' => $especialidad->created_at?->toIso8601String(),
            'updated_at' => $especialidad->updated_at?->toIso8601String(),
        ];
    }
}
