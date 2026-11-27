<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\PacientesPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo 04.01 — Registro de pacientes.
 *
 * Los permisos de tutor pertenecen a TutoresPermissionSeeder (04.02).
 */
final class PacientesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PacientesPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign('pacientes', PacientesPermission::all());

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(PacientesPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                PacientesPermission::PATIENT_VIEW,
                PacientesPermission::PATIENT_EDIT,
                PacientesPermission::PATIENT_CHANGE_STATUS,
                PacientesPermission::PATIENT_RETIRE,
                PacientesPermission::PATIENT_VIEW_SENSITIVE,
                PacientesPermission::PATIENT_EXPORT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                PacientesPermission::PATIENT_VIEW,
                PacientesPermission::PATIENT_CREATE,
                PacientesPermission::PATIENT_EDIT,
                PacientesPermission::PATIENT_VIEW_SENSITIVE,
                PacientesPermission::PATIENT_EXPORT,
            ]);

            InitialPermissionMatrix::mark('pacientes');
        }

        /*
         * Migración de la separación de permisos de 06.05: Recepción deja de
         * cambiar el estado del paciente y Administración gana pausa,
         * reactivación y retiro. Marcador propio para no pisar revocaciones
         * manuales posteriores.
         */
        if (InitialPermissionMatrix::shouldAssign(
            'pacientes.estado_split',
            [PacientesPermission::PATIENT_RETIRE],
        )) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo([
                PacientesPermission::PATIENT_CHANGE_STATUS,
                PacientesPermission::PATIENT_RETIRE,
            ]);

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                PacientesPermission::PATIENT_CHANGE_STATUS,
                PacientesPermission::PATIENT_RETIRE,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->revokePermissionTo(PacientesPermission::PATIENT_CHANGE_STATUS);

            InitialPermissionMatrix::mark('pacientes.estado_split');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
