<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 02 de inscripciones (paquetes).
 */
final class PaquetesSesionesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PaquetePermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'inscripciones.paquetes_sesiones',
            PaquetePermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(PaquetePermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                PaquetePermission::VIEW,
                PaquetePermission::CREATE,
                PaquetePermission::EDIT,
                PaquetePermission::RENEW,
                PaquetePermission::WITHDRAW,
                PaquetePermission::EXPORT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                PaquetePermission::VIEW,
                PaquetePermission::CREATE,
                PaquetePermission::RENEW,
            ]);

            InitialPermissionMatrix::mark('inscripciones.paquetes_sesiones');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
