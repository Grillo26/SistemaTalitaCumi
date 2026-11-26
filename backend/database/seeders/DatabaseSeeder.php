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
        $this->call(AreaSeeder::class);
        $this->call(TipoServicioSeeder::class);
        $this->call(OrganizacionSalasAmbientesCatalogSeeder::class);
        $this->call(OrganizacionConfiguracionPermissionSeeder::class);
        $this->call(OrganizacionConfiguracionCatalogSeeder::class);
        $this->call(SeguridadPermissionSeeder::class);
        $this->call(SeguridadMatrixBaselineSeeder::class);
        $this->call(AccionAuditadaSeeder::class);
        $this->call(ProfesionalesPermissionSeeder::class);
        $this->call(EspecialidadCatalogoSeeder::class);
        $this->call(ProfesionalesAccionAuditadaSeeder::class);
        $this->call(PacientesPermissionSeeder::class);
        $this->call(PacientesAccionAuditadaSeeder::class);
        $this->call(ParentescoCatalogoSeeder::class);
        $this->call(TutoresPermissionSeeder::class);
        $this->call(TutoresAccionAuditadaSeeder::class);
        $this->call(ContactosEmergenciaPermissionSeeder::class);
        $this->call(ContactosEmergenciaAccionAuditadaSeeder::class);
        $this->call(FichaIngresoPermissionSeeder::class);
        $this->call(FichaIngresoAccionAuditadaSeeder::class);
        $this->call(ConsentimientoPermissionSeeder::class);
        $this->call(ConsentimientoTipoSeeder::class);
        $this->call(ConsentimientoAccionAuditadaSeeder::class);
        $this->call(ObservacionPermissionSeeder::class);
        $this->call(ObservacionAccionAuditadaSeeder::class);
        $this->call(PacienteResumenPermissionSeeder::class);
        $this->call(CatalogoServiciosPermissionSeeder::class);
        $this->call(CatalogoServiciosAccionAuditadaSeeder::class);
        $this->call(CatalogoTarifasPermissionSeeder::class);
        $this->call(CatalogoTarifasAccionAuditadaSeeder::class);
        $this->call(CategoriaTarifaSeeder::class);
        $this->call(CatalogoDiagnosticosPermissionSeeder::class);
        $this->call(CatalogoDiagnosticosAccionAuditadaSeeder::class);
        $this->call(InscripcionesAtencionesPermissionSeeder::class);
        $this->call(InscripcionesAtencionesAccionAuditadaSeeder::class);
        $this->call(PaquetesSesionesPermissionSeeder::class);
        $this->call(PaquetesSesionesAccionAuditadaSeeder::class);
        $this->call(PaquetesSesionesConfiguracionSeeder::class);
        $this->call(EntrevistaDiagnosticoPermissionSeeder::class);
        $this->call(EntrevistaDiagnosticoAccionAuditadaSeeder::class);
    }
}
