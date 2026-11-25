<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de la ficha de ingreso (04.03 Fase A).
 *
 * Matriz aprobada: Recepción crea/edita y no exporta; Administración edita y
 * archiva; Superadmin reactiva. La exportación queda reservada.
 */
final class FichaIngresoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (FichaIngresoPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        if (InitialPermissionMatrix::shouldAssign('pacientes.ficha_ingreso', FichaIngresoPermission::all())) {
            Role::findByName('superadmin', 'web')
                ->givePermissionTo(FichaIngresoPermission::all());

            Role::findByName('administracion', 'web')
                ->givePermissionTo([
                    FichaIngresoPermission::VIEW,
                    FichaIngresoPermission::EDIT,
                    FichaIngresoPermission::ARCHIVE,
                    FichaIngresoPermission::VIEW_SENSITIVE,
                    FichaIngresoPermission::EXPORT,
                ]);

            Role::findByName('recepcion', 'web')
                ->givePermissionTo([
                    FichaIngresoPermission::VIEW,
                    FichaIngresoPermission::CREATE,
                    FichaIngresoPermission::EDIT,
                    FichaIngresoPermission::VIEW_SENSITIVE,
                ]);

            InitialPermissionMatrix::mark('pacientes.ficha_ingreso');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
