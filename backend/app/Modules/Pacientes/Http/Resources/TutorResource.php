<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Support\TutorAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación del tutor (04.02/RF-008).
 */
final class TutorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $actor, Tutor $tutor, Request $request): array
    {
        $data = (new self($tutor))->resolve($request);
        $data['can'] = TutorAbilities::for($actor, $tutor);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Tutor $tutor */
        $tutor = $this->resource;

        return [
            'id' => (int) $tutor->id,
            'nombre_completo' => $tutor->nombre_completo,
            'ci' => $tutor->ci,
            'telefono' => $tutor->telefono,
            'telefono_alternativo' => $tutor->telefono_alternativo,
            'email' => $tutor->email,
            'direccion' => $tutor->direccion,
            'parentesco' => $tutor->parentesco,
            'activo' => (bool) $tutor->activo,
            'estado' => $tutor->estado,
            'version' => (int) $tutor->version,
            'created_at' => $tutor->created_at?->toIso8601String(),
            'updated_at' => $tutor->updated_at?->toIso8601String(),
        ];
    }
}
