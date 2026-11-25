<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Tutor;

/**
 * Snapshot del tutor para bitácora (04.02/RF-014).
 *
 * Guarda valores completos porque la bitácora exige valores anteriores/nuevos
 * (04.02/CA-005) y solo la consulta quien tiene `bitacora.ver` con alcance por
 * sucursal. En listados el teléfono se enmascara.
 */
final class TutorSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Tutor $tutor): array
    {
        $tutor->loadMissing('parentescoCatalogo');

        return [
            'id' => (int) $tutor->id,
            'sucursal_registro_id' => $tutor->sucursal_registro_id === null ? null : (int) $tutor->sucursal_registro_id,
            'nombre_completo' => $tutor->nombre_completo,
            'ci' => $tutor->ci,
            'telefono' => $tutor->telefono,
            'telefono_alternativo' => $tutor->telefono_alternativo,
            'email' => $tutor->email,
            'direccion' => $tutor->direccion,
            'parentesco_id' => $tutor->parentesco_id === null ? null : (int) $tutor->parentesco_id,
            'parentesco' => $tutor->parentescoCatalogo?->nombre ?? $tutor->parentesco,
            'activo' => (bool) $tutor->activo,
            'version' => (int) $tutor->version,
            'updated_at' => $tutor->updated_at?->toIso8601String(),
        ];
    }
}
