<?php

namespace Tests\Feature\Modules\Pacientes;

use Database\Seeders\PacientesAccionAuditadaSeeder;
use Database\Seeders\PacientesPermissionSeeder;
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
        $this->assertFalse($administracion->hasPermissionTo('paciente.cambiar_estado'));
        $this->assertTrue($recepcion->hasPermissionTo('paciente.crear'));
        $this->assertTrue($recepcion->hasPermissionTo('paciente.cambiar_estado'));
        $this->assertTrue($recepcion->hasPermissionTo('tutor.crear'));
    }

    public function test_reseeding_does_not_restore_revoked_permissions(): void
    {
        $recepcion = Role::findByName('recepcion', 'web');
        $recepcion->revokePermissionTo('paciente.crear');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(PacientesPermissionSeeder::class);

        $this->assertFalse($recepcion->fresh()->hasPermissionTo('paciente.crear'));
    }

    public function test_audited_action_catalog_is_idempotent(): void
    {
        $this->seed(PacientesAccionAuditadaSeeder::class);
        $this->seed(PacientesAccionAuditadaSeeder::class);

        $this->assertSame(
            10,
            \App\Modules\Seguridad\Models\AccionAuditada::query()
                ->where('modulo', 'pacientes')
                ->count(),
        );
    }
}
