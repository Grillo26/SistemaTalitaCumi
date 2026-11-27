<?php

namespace Tests\Feature\Modules\Pacientes;

use App\Modules\Seguridad\Models\AccionAuditada;
use Database\Seeders\ConsentimientoAccionAuditadaSeeder;
use Database\Seeders\ContactosEmergenciaAccionAuditadaSeeder;
use Database\Seeders\FichaIngresoAccionAuditadaSeeder;
use Database\Seeders\ObservacionAccionAuditadaSeeder;
use Database\Seeders\PacientesAccionAuditadaSeeder;
use Database\Seeders\PacientesPermissionSeeder;
use Database\Seeders\TutoresAccionAuditadaSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PacienteSeederTest extends PacientesTestCase
{
    public function test_permissions_are_seeded_with_expected_matrix(): void
    {
        $superadmin = Role::findByName('superadmin', 'web');
        $administracion = Role::findByName('administracion', 'web');
        $recepcion = Role::findByName('recepcion', 'web');

        $this->assertTrue($superadmin->hasPermissionTo('paciente.crear'));
        $this->assertFalse($administracion->hasPermissionTo('paciente.crear'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.editar'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.cambiar_estado'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.retirar'));
        $this->assertTrue($recepcion->hasPermissionTo('paciente.crear'));
        $this->assertFalse($recepcion->hasPermissionTo('paciente.cambiar_estado'));
        $this->assertFalse($recepcion->hasPermissionTo('paciente.retirar'));
        $this->assertTrue($recepcion->hasPermissionTo('tutor.crear'));
        $this->assertTrue($recepcion->hasPermissionTo('paciente.ver_contacto_emergencia'));
        $this->assertTrue($recepcion->hasPermissionTo('paciente.editar_contacto_emergencia'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.ver_contacto_emergencia'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.editar_contacto_emergencia'));
        $this->assertTrue($recepcion->hasPermissionTo('ficha_ingreso.consentimiento_ver'));
        $this->assertTrue($recepcion->hasPermissionTo('ficha_ingreso.consentimiento_registrar'));
        $this->assertFalse($recepcion->hasPermissionTo('ficha_ingreso.consentimiento_revocar'));
        $this->assertTrue($administracion->hasPermissionTo('ficha_ingreso.consentimiento_revocar'));
        $this->assertFalse($administracion->hasPermissionTo('ficha_ingreso.consentimiento_catalogo'));
        $this->assertTrue($superadmin->hasPermissionTo('ficha_ingreso.consentimiento_catalogo'));
        $this->assertTrue($recepcion->hasPermissionTo('ficha_ingreso.observacion_ver'));
        $this->assertTrue($recepcion->hasPermissionTo('ficha_ingreso.observacion_agregar'));
        $this->assertTrue($administracion->hasPermissionTo('ficha_ingreso.observacion_agregar'));
        $this->assertTrue($superadmin->hasPermissionTo('paciente.exportar_listado'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.exportar_listado'));
        $this->assertFalse($recepcion->hasPermissionTo('paciente.exportar_listado'));
    }

    public function test_reseeding_does_not_restore_revoked_permissions(): void
    {
        $recepcion = Role::findByName('recepcion', 'web');
        $recepcion->revokePermissionTo('paciente.crear');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(PacientesPermissionSeeder::class);

        $this->assertFalse($recepcion->fresh()->hasPermissionTo('paciente.crear'));
    }

    public function test_audited_action_catalogs_are_idempotent(): void
    {
        $this->seed(PacientesAccionAuditadaSeeder::class);
        $this->seed(PacientesAccionAuditadaSeeder::class);
        $this->seed(TutoresAccionAuditadaSeeder::class);
        $this->seed(TutoresAccionAuditadaSeeder::class);
        $this->seed(ContactosEmergenciaAccionAuditadaSeeder::class);
        $this->seed(ContactosEmergenciaAccionAuditadaSeeder::class);
        $this->seed(FichaIngresoAccionAuditadaSeeder::class);
        $this->seed(FichaIngresoAccionAuditadaSeeder::class);
        $this->seed(ConsentimientoAccionAuditadaSeeder::class);
        $this->seed(ConsentimientoAccionAuditadaSeeder::class);
        $this->seed(ObservacionAccionAuditadaSeeder::class);
        $this->seed(ObservacionAccionAuditadaSeeder::class);

        $this->assertSame(
            30,
            AccionAuditada::query()
                ->where('modulo', 'pacientes')
                ->count(),
        );
    }
}
