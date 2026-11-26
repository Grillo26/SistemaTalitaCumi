<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 02 (RF-T17).
 *
 * Marcador propio (`catalogo.tarifas`) para no pisar la matriz del 01.
 * Administración edita tarifas de sus sucursales; categorías solo consulta.
 */
final class CatalogoTarifasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (CatalogoServiciosPermission::tarifas() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'catalogo.tarifas',
            CatalogoServiciosPermission::tarifas(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(CatalogoServiciosPermission::tarifas());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                CatalogoServiciosPermission::TARIFA_VIEW,
                CatalogoServiciosPermission::TARIFA_EDIT,
                CatalogoServiciosPermission::CATEGORIA_VIEW,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                CatalogoServiciosPermission::TARIFA_VIEW,
                CatalogoServiciosPermission::CATEGORIA_VIEW,
            ]);

            InitialPermissionMatrix::mark('catalogo.tarifas');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
