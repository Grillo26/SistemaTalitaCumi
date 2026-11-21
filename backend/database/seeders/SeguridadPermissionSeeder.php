<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Support\SecurityPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo 02 — Usuarios, roles y seguridad.
 *
 * La única capa que nombra roles es esta matriz inicial. El código decide
 * siempre por permiso. La asignación se hace una sola vez por módulo para
 * no pisar cambios hechos desde la UI (02/RF-R04).
 */
final class SeguridadPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (SecurityPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign('seguridad', SecurityPermission::all());

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(SecurityPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                SecurityPermission::USER_VIEW,
                SecurityPermission::USER_EXPORT,
                SecurityPermission::ROLE_VIEW,
                SecurityPermission::MATRIX_EXPORT,
                SecurityPermission::AUDIT_VIEW,
                SecurityPermission::AUDIT_EXPORT,
            ]);

            // Recepción no tiene acceso a usuarios, roles ni bitácora.

            InitialPermissionMatrix::mark('seguridad');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
