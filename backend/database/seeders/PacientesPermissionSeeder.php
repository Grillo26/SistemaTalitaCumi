<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\PacientesPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo 04 — Pacientes y tutores.
 *
 * La única capa que nombra roles es esta matriz inicial. Recepción crea
 * pacientes; Administración consulta y edita. Superadmin tiene todo.
 */
final class PacientesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PacientesPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'pacientes',
            PacientesPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(PacientesPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                PacientesPermission::PATIENT_VIEW,
                PacientesPermission::PATIENT_EDIT,
                PacientesPermission::PATIENT_VIEW_SENSITIVE,
                PacientesPermission::PATIENT_EXPORT,
                PacientesPermission::TUTOR_VIEW,
                PacientesPermission::TUTOR_EDIT,
                PacientesPermission::TUTOR_EXPORT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                PacientesPermission::PATIENT_VIEW,
                PacientesPermission::PATIENT_CREATE,
                PacientesPermission::PATIENT_EDIT,
                PacientesPermission::PATIENT_CHANGE_STATUS,
                PacientesPermission::PATIENT_VIEW_SENSITIVE,
                PacientesPermission::PATIENT_EXPORT,
                PacientesPermission::TUTOR_VIEW,
                PacientesPermission::TUTOR_CREATE,
                PacientesPermission::TUTOR_EDIT,
                PacientesPermission::TUTOR_EXPORT,
            ]);

            InitialPermissionMatrix::mark('pacientes');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
