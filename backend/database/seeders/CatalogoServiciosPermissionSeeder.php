<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 01 (RF-C16).
 *
 * La única capa que nombra roles es esta matriz inicial. El código decide
 * por permiso.
 */
final class CatalogoServiciosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (CatalogoServiciosPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'catalogo.servicios',
            CatalogoServiciosPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(CatalogoServiciosPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                CatalogoServiciosPermission::AREA_VIEW,
                CatalogoServiciosPermission::SERVICIO_VIEW,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                CatalogoServiciosPermission::AREA_VIEW,
                CatalogoServiciosPermission::SERVICIO_VIEW,
            ]);

            InitialPermissionMatrix::mark('catalogo.servicios');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
