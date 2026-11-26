<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\VigenciaPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 04 (vigencia, vencimiento y renovación).
 */
final class VigenciasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (VigenciaPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'inscripciones.vigencias',
            VigenciaPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(VigenciaPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(VigenciaPermission::all());

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                VigenciaPermission::VIEW,
            ]);

            InitialPermissionMatrix::mark('inscripciones.vigencias');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
