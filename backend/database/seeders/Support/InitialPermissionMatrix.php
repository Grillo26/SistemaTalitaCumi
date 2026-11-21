<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\DB;

/**
 * Marca de matriz inicial por módulo (02/RF-R04).
 *
 * Evita que un seeder re-ejecutado vuelva a conceder permisos que el
 * Superadmin revocó desde la UI. Es autocurativo:
 *
 * - Si el módulo ya está marcado, no reasigna.
 * - Si detecta asignaciones previas (instalación existente), marca y no reasigna.
 * - Si no hay asignaciones (instalación nueva), permite la asignación inicial
 *   y el seeder debe llamar a mark() al terminar.
 */
final class InitialPermissionMatrix
{
    /**
     * @param  list<string>  $permissionNames
     */
    public static function shouldAssign(string $module, array $permissionNames): bool
    {
        if (DB::table('modulo_permisos_inicializado')->where('modulo', $module)->exists()) {
            return false;
        }

        $alreadyAssigned = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('permissions.name', $permissionNames)
            ->exists();

        if ($alreadyAssigned) {
            self::mark($module);

            return false;
        }

        return true;
    }

    public static function mark(string $module): void
    {
        DB::table('modulo_permisos_inicializado')->insertOrIgnore([
            'modulo' => $module,
            'inicializado_en' => now(),
        ]);
    }
}
