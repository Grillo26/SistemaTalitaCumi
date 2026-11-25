<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\FichaIngresoRevision;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Illuminate\Http\Request;

/**
 * Revisión de ficha (04.03/RF-005).
 *
 * Los metadatos se muestran con permiso de ficha. El snapshot completo solo
 * se envía con `ficha_ingreso.ver_sensibles`.
 */
final class FichaIngresoRevisionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $actor, FichaIngresoRevision $revision, Request $request): array
    {
        $revision->loadMissing('usuario');
        $fullSensitive = $actor->can(FichaIngresoPermission::VIEW_SENSITIVE);

        return [
            'id' => (int) $revision->id,
            'version' => (int) $revision->version,
            'accion' => $revision->accion,
            'usuario_id' => (int) $revision->usuario_id,
            'usuario_nombre' => $revision->usuario?->name,
            'campos_modificados' => $revision->campos_modificados ?? [],
            'snapshot' => $fullSensitive ? $revision->snapshot : null,
            'contenido_protegido' => ! $fullSensitive,
            'created_at' => $revision->created_at?->toIso8601String(),
        ];
    }
}
