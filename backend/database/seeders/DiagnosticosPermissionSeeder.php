<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 07.05/RF-007 / 07.05/RF-010.
 *
 * Recepción coordina y programa con los permisos de 03; solo Administración y
 * Superadmin cancelan el diagnóstico o actualizan manualmente el estado
 * general. La decisión es por permiso, nunca por nombre de rol.
 */
final class DiagnosticosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (DiagnosticosPermission::nuevos() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'agenda.diagnosticos',
            DiagnosticosPermission::nuevos(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(DiagnosticosPermission::nuevos());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(DiagnosticosPermission::nuevos());

            InitialPermissionMatrix::mark('agenda.diagnosticos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
