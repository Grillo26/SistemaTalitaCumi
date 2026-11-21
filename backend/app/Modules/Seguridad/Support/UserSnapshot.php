<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot seguro de usuario para bitácora (02/RF-U08, 02/RF-B10).
 *
 * Incluye solo datos de negocio. Nunca contraseñas, hashes, tokens ni
 * remember_token.
 */
final class UserSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(User $user): array
    {
        $role = $user->getRoleNames()->first();

        $branchIds = DB::table('usuario_sucursal')
            ->where('usuario_id', $user->id)
            ->orderBy('sucursal_id')
            ->pluck('sucursal_id')
            ->all();

        return [
            'id' => $user->id,
            'nombre_completo' => $user->name,
            'username' => $user->username,
            'documento' => $user->documento,
            'email' => $user->email,
            'activo' => (bool) $user->activo,
            'rol' => $role,
            'sucursales' => $branchIds,
            'version' => $user->version,
            'ultimo_acceso' => $user->ultimo_acceso?->toIso8601String(),
            'debe_cambiar_password' => (bool) $user->debe_cambiar_password,
            'motivo_estado' => $user->motivo_estado,
            'estado_cambiado_en' => $user->estado_cambiado_en?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
