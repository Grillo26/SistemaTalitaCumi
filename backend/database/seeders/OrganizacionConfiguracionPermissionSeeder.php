<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Support\ConfiguracionPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo de configuración.
 *
 * La única capa que nombra roles es esta matriz; el código decide por permiso.
 * La asignación inicial se ejecuta una sola vez por módulo (RF-R04).
 */
final class OrganizacionConfiguracionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ConfiguracionPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'organizacion.configuracion',
            ConfiguracionPermission::all(),
        );

        if ($assign) {
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

            InitialPermissionMatrix::mark('organizacion.configuracion');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
