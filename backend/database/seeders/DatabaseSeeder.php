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
     * Los permisos concretos se definen por módulo. Este seeder es
     * idempotente y no depende de Faker ni de paquetes de desarrollo.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['superadmin', 'administracion', 'recepcion'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // 02 / RF-R01, RN-16: el rol protegido se identifica por dato,
            // no por nombre, para que el código no dependa de la etiqueta.
            $role->forceFill([
                'es_superadmin' => $roleName === 'superadmin',
            ])->save();
        }

        $this->call(OrganizacionSucursalesPermissionSeeder::class);
        $this->call(OrganizacionSalasAmbientesPermissionSeeder::class);
        $this->call(OrganizacionSalasAmbientesCatalogSeeder::class);
        $this->call(OrganizacionConfiguracionPermissionSeeder::class);
        $this->call(OrganizacionConfiguracionCatalogSeeder::class);
        $this->call(SeguridadPermissionSeeder::class);
        $this->call(SeguridadMatrixBaselineSeeder::class);
        $this->call(AccionAuditadaSeeder::class);
    }
}
