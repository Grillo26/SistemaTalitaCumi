<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\ObservacionPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de observaciones fechadas (04.03 Fase C).
 */
final class ObservacionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ObservacionPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        if (InitialPermissionMatrix::shouldAssign('pacientes.ficha_ingreso_observacion', ObservacionPermission::all())) {
            Role::findByName('superadmin', 'web')
                ->givePermissionTo(ObservacionPermission::all());

            Role::findByName('administracion', 'web')
                ->givePermissionTo(ObservacionPermission::all());

            Role::findByName('recepcion', 'web')
                ->givePermissionTo(ObservacionPermission::all());

            InitialPermissionMatrix::mark('pacientes.ficha_ingreso_observacion');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
