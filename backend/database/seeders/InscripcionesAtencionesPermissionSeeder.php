<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 01 de inscripciones (RF-016).
 *
 * Recepción registra, consulta y renueva. Administración además modifica
 * y da de baja. La decisión siempre se toma por permiso, no por rol.
 */
final class InscripcionesAtencionesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (InscripcionPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'inscripciones.salas_grupales',
            InscripcionPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(InscripcionPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                InscripcionPermission::VIEW,
                InscripcionPermission::CREATE,
                InscripcionPermission::EDIT,
                InscripcionPermission::RENEW,
                InscripcionPermission::WITHDRAW,
                InscripcionPermission::EXPORT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                InscripcionPermission::VIEW,
                InscripcionPermission::CREATE,
                InscripcionPermission::RENEW,
            ]);

            InitialPermissionMatrix::mark('inscripciones.salas_grupales');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
