<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Tutor;

/**
 * Snapshot del tutor para bitácora (04.02/RF-014).
 */
final class TutorSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Tutor $tutor): array
    {
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
            'version' => (int) $tutor->version,
            'updated_at' => $tutor->updated_at?->toIso8601String(),
        ];
    }
}
