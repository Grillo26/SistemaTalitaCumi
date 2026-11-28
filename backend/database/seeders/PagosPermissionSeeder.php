<?php

namespace Database\Seeders;

use App\Modules\Pagos\Support\PagoPermission;
use App\Modules\Pagos\Support\PagosModule;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 08.01. Matriz inicial: Recepción ve y registra;
 * Administración además autoriza fecha futura y exporta; Superadmin todo.
 */
final class PagosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PagoPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        if (InitialPermissionMatrix::shouldAssign(PagosModule::MARCA_INICIALIZACION, PagoPermission::all())) {
            Role::findByName('superadmin', 'web')->givePermissionTo(PagoPermission::all());

            Role::findByName('administracion', 'web')->givePermissionTo(PagoPermission::all());

            Role::findByName('recepcion', 'web')->givePermissionTo([
                PagoPermission::VIEW,
                PagoPermission::REGISTER,
            ]);

            InitialPermissionMatrix::mark(PagosModule::MARCA_INICIALIZACION);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
