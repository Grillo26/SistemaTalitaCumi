<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Support\BranchPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo de sucursales.
 *
 * La matriz rol → permiso es la única capa donde se nombran roles. El código
 * de la aplicación siempre decide por permiso. La asignación inicial se
 * ejecuta una sola vez por módulo para no pisar cambios hechos por UI.
 */
final class OrganizacionSucursalesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (BranchPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'organizacion.sucursales',
            BranchPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(BranchPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                BranchPermission::VIEW,
                BranchPermission::EDIT_CONTACT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo(BranchPermission::VIEW);

            InitialPermissionMatrix::mark('organizacion.sucursales');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
