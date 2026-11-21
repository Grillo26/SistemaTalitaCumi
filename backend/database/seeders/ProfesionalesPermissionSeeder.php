<?php

namespace Database\Seeders;

use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo 03 — Profesionales.
 *
 * La única capa que nombra roles es esta matriz inicial. Los permisos de
 * asignación quedan sembrados y documentados aunque no tengan endpoint hasta
 * que exista el módulo 07.
 */
final class ProfesionalesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ProfesionalesPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'profesionales',
            ProfesionalesPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(ProfesionalesPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                ProfesionalesPermission::PROFESSIONAL_VIEW,
                ProfesionalesPermission::PROFESSIONAL_EDIT_CONTACT,
                ProfesionalesPermission::PROFESSIONAL_EXPORT,
                ProfesionalesPermission::PROFESSIONAL_VIEW_MATRIX,
                ProfesionalesPermission::SPECIALTY_VIEW,
                ProfesionalesPermission::ASSIGNMENT_VIEW,
                ProfesionalesPermission::ASSIGNMENT_CREATE,
                ProfesionalesPermission::ASSIGNMENT_REASSIGN,
                ProfesionalesPermission::ASSIGNMENT_UNASSIGN,
                ProfesionalesPermission::ASSIGNMENT_EXPORT,
                ProfesionalesPermission::DIAGNOSTIC_ASSIGN,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                ProfesionalesPermission::PROFESSIONAL_VIEW,
                ProfesionalesPermission::PROFESSIONAL_VIEW_MATRIX,
                ProfesionalesPermission::SPECIALTY_VIEW,
                ProfesionalesPermission::ASSIGNMENT_VIEW,
                ProfesionalesPermission::ASSIGNMENT_CREATE,
                ProfesionalesPermission::ASSIGNMENT_REASSIGN,
                ProfesionalesPermission::ASSIGNMENT_UNASSIGN,
                ProfesionalesPermission::DIAGNOSTIC_ASSIGN,
            ]);

            InitialPermissionMatrix::mark('profesionales');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
