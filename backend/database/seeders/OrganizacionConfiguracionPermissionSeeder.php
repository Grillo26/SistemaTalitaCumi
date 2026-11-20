<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Support\ConfiguracionPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo de configuración.
 *
 * La única capa que nombra roles es esta matriz; el código decide por permiso.
 */
final class OrganizacionConfiguracionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ConfiguracionPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superadmin = Role::findByName('superadmin', 'web');
        $superadmin->givePermissionTo(ConfiguracionPermission::all());

        $administracion = Role::findByName('administracion', 'web');
        $administracion->givePermissionTo([
            ConfiguracionPermission::VIEW,
            ConfiguracionPermission::EXPORT,
        ]);

        $recepcion = Role::findByName('recepcion', 'web');
        $recepcion->givePermissionTo([
            ConfiguracionPermission::VIEW_OPERATIVES,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
