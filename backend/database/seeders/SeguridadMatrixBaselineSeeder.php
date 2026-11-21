<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Events\RolePermissionsUpdated;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Línea base de la matriz en bitácora (02/RF-R14).
 *
 * Se ejecuta una sola vez por instalación. Permite reconstruir la matriz
 * vigente en una fecha incluso antes de la primera edición por UI.
 */
final class SeguridadMatrixBaselineSeeder extends Seeder
{
    private const MARKER = 'seguridad.matriz_baseline';

    public function run(): void
    {
        if (DB::table('modulo_permisos_inicializado')->where('modulo', self::MARKER)->exists()) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $roles = Role::query()
                ->where('guard_name', 'web')
                ->with('permissions:id,name')
                ->orderBy('id')
                ->get();

            foreach ($roles as $role) {
                $permissions = $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all();

                event(new RolePermissionsUpdated(
                    roleId: $role->id,
                    before: [],
                    after: $permissions,
                    beforeVersion: null,
                    version: (int) $role->version,
                    actorId: null,
                    ip: null,
                    motivo: 'Línea base de instalación',
                    baseline: true,
                ));
            }

            DB::table('modulo_permisos_inicializado')->insertOrIgnore([
                'modulo' => self::MARKER,
                'inicializado_en' => now(),
            ]);
        });
    }
}
