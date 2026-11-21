<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Modules\Pacientes\Models\BitacoraEntry;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteAuditTest extends PacientesTestCase
{
    public function test_audit_snapshots_do_not_store_clinical_content(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'alergias' => 'Penicilina',
            'condiciones_medicas' => 'Asma',
            'observaciones' => 'Texto clínico que no debe copiarse',
        ], $branch))->assertCreated()->json('data');

        $createAudit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CREAR')
            ->firstOrFail();

        $this->assertTrue($createAudit->datos_nuevos['tiene_alergias']);
        $this->assertTrue($createAudit->datos_nuevos['tiene_condiciones_medicas']);
        $this->assertTrue($createAudit->datos_nuevos['tiene_observaciones']);
        $this->assertArrayNotHasKey('alergias', $createAudit->datos_nuevos);
        $this->assertArrayNotHasKey('condiciones_medicas', $createAudit->datos_nuevos);
        $this->assertArrayNotHasKey('observaciones', $createAudit->datos_nuevos);

        $this->patchJson('/api/pacientes/pacientes/'.$created['id'], [
            'alergias' => null,
            'observaciones' => 'Otro texto clínico',
            'version' => $created['version'],
        ])->assertOk();

        $updateAudit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertContains('alergias', $updateAudit->datos_nuevos['campos_sensibles_modificados']);
        $this->assertContains('observaciones', $updateAudit->datos_nuevos['campos_sensibles_modificados']);
        $this->assertArrayNotHasKey('alergias', $updateAudit->datos_nuevos);
        $this->assertArrayNotHasKey('observaciones', $updateAudit->datos_nuevos);
    }

    public function test_audit_action_catalog_contains_module_entries(): void
    {
        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'pacientes',
            'entidad' => 'paciente',
            'accion' => 'CREAR',
        ]);
        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'pacientes',
            'entidad' => 'paciente',
            'accion' => 'CAMBIAR_ESTADO',
            'critico' => true,
        ]);
        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'pacientes',
            'entidad' => 'paciente_tutor',
            'accion' => 'ASOCIAR',
        ]);
    }
}
