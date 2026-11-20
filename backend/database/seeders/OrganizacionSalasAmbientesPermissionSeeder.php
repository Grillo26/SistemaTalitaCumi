<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Support\SalasAmbientesPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo de salas y ambientes.
 *
 * La única capa que nombra roles es esta matriz; el código decide por permiso.
 */
final class OrganizacionSalasAmbientesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (SalasAmbientesPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superadmin = Role::findByName('superadmin', 'web');
        $superadmin->givePermissionTo(SalasAmbientesPermission::all());

        $administracion = Role::findByName('administracion', 'web');
        $administracion->givePermissionTo([
            SalasAmbientesPermission::CATALOG_VIEW,
            SalasAmbientesPermission::SALA_VIEW,
            SalasAmbientesPermission::SALA_EDIT_DATA,
            SalasAmbientesPermission::AMBIENTE_VIEW,
            SalasAmbientesPermission::AMBIENTE_EDIT_DATA,
            SalasAmbientesPermission::EXPORT,
        ]);

        $recepcion = Role::findByName('recepcion', 'web');
        $recepcion->givePermissionTo([
            SalasAmbientesPermission::CATALOG_VIEW,
            SalasAmbientesPermission::SALA_VIEW,
            SalasAmbientesPermission::AMBIENTE_VIEW,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
