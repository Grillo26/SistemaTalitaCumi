<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de contactos de emergencia (04.02/RF-012).
 *
 * Matriz independiente para no revertir ajustes del Superadmin. La primera
 * vez asigna a Superadmin, Administración y Recepción; después, la marca
 * `pacientes.contactos_emergencia` evita reasignaciones.
 */
final class ContactosEmergenciaPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PacientesPermission::contactoEmergenciaAll() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $initialized = DB::table('modulo_permisos_inicializado')
            ->where('modulo', 'pacientes.contactos_emergencia')
            ->exists();

        if (! $initialized) {
            foreach (['superadmin', 'administracion', 'recepcion'] as $roleName) {
                Role::findByName($roleName, 'web')
                    ->givePermissionTo(PacientesPermission::contactoEmergenciaAll());
            }

            DB::table('modulo_permisos_inicializado')->insertOrIgnore([
                'modulo' => 'pacientes.contactos_emergencia',
                'inicializado_en' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
