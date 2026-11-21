<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\ConsentimientoPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de consentimientos (04.03 Fase B).
 *
 * Recepción registra y consulta; Administración además revoca; el catálogo
 * queda reservado a Superadmin.
 */
final class ConsentimientoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (ConsentimientoPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        if (InitialPermissionMatrix::shouldAssign('pacientes.ficha_ingreso_consentimiento', ConsentimientoPermission::all())) {
            Role::findByName('superadmin', 'web')
                ->givePermissionTo(ConsentimientoPermission::all());

            Role::findByName('administracion', 'web')
                ->givePermissionTo([
                    ConsentimientoPermission::VIEW,
                    ConsentimientoPermission::REGISTER,
                    ConsentimientoPermission::REVOKE,
                ]);

            Role::findByName('recepcion', 'web')
                ->givePermissionTo([
                    ConsentimientoPermission::VIEW,
                    ConsentimientoPermission::REGISTER,
                ]);

            InitialPermissionMatrix::mark('pacientes.ficha_ingreso_consentimiento');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
