<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles base del sistema.
     *
     * Los permisos concretos se irán definiendo por módulo. Este seeder es
     * idempotente y no depende de Faker ni de paquetes de desarrollo.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['superadmin', 'administracion', 'recepcion'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $this->call(OrganizacionSucursalesPermissionSeeder::class);
    }
}
