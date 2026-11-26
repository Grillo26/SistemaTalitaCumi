<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 03 (RF-D12 / RN-D10).
 */
final class CatalogoDiagnosticosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (CatalogoServiciosPermission::diagnosticos() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'catalogo.diagnosticos',
            CatalogoServiciosPermission::diagnosticos(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(CatalogoServiciosPermission::diagnosticos());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW,
            ]);

            InitialPermissionMatrix::mark('catalogo.diagnosticos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
