<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\AtencionPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 03 (entrevistas y diagnósticos).
 */
final class EntrevistaDiagnosticoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AtencionPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'inscripciones.entrevista_diagnostico',
            AtencionPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(AtencionPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(AtencionPermission::all());

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                AtencionPermission::VIEW,
                AtencionPermission::CREATE,
            ]);

            InitialPermissionMatrix::mark('inscripciones.entrevista_diagnostico');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
