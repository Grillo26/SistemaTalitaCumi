<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Modules\Pacientes\Models\BitacoraEntry;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteStatusTest extends PacientesTestCase
{
    public function test_pause_requires_reason_and_is_not_critical(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'en_pausa',
            'motivo' => '',
            'version' => $created['version'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);

        $paused = $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje familiar',
            'version' => $created['version'],
        ])->assertOk()->json('data');

        $this->assertSame('en_pausa', $paused['estado']);

        $this->assertDatabaseHas('estado_paciente_historial', [
            'paciente_id' => $created['id'],
            'estado_anterior' => 'activo',
            'estado_nuevo' => 'en_pausa',
            'motivo' => 'Viaje familiar',
        ]);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CAMBIAR_ESTADO')
            ->latest('id')
            ->firstOrFail();

        $this->assertFalse($audit->critico);
        $this->assertSame('Viaje familiar', $audit->motivo);
    }

    public function test_retirement_and_return_are_critical_and_require_reason(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'retirado',
            'version' => $created['version'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);

        $retired = $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'retirado',
            'motivo' => 'La familia se muda',
            'version' => $created['version'],
        ])->assertOk()->json('data');

        $retireAudit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CAMBIAR_ESTADO')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($retireAudit->critico);
        $this->assertSame('La familia se muda', $retireAudit->motivo);

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'activo',
            'version' => $retired['version'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'activo',
            'motivo' => 'La familia regresó',
            'version' => $retired['version'],
        ])->assertOk()
            ->assertJsonPath('data.estado', 'activo');

        $returnAudit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CAMBIAR_ESTADO')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($returnAudit->critico);
        $this->assertSame('La familia regresó', $returnAudit->motivo);
    }

    public function test_same_state_and_invalid_transitions_are_rejected(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();
        $this->withBranch($user, $branch);

        $active = $this->paciente(['sucursal_registro_id' => $branch->id, 'estado' => 'activo']);
        $retired = $this->paciente(['sucursal_registro_id' => $branch->id, 'estado' => 'retirado']);

        $this->postJson('/api/pacientes/pacientes/'.$active->id.'/estado', [
            'estado' => 'activo',
            'motivo' => 'No aplica',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_STATUS_UNCHANGED');

        $this->postJson('/api/pacientes/pacientes/'.$retired->id.'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'No aplica',
            'version' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'PATIENT_STATUS_TRANSITION_NOT_ALLOWED');
    }

    public function test_activation_without_principal_tutor_is_blocked(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();
        $this->withBranch($user, $branch);

        $paciente = $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'estado' => 'en_pausa',
        ]);

        $this->postJson('/api/pacientes/pacientes/'.$paciente->id.'/estado', [
            'estado' => 'activo',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_TUTOR_REQUIRED');
    }

    public function test_stale_version_conflicts(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();
        $this->withBranch($user, $branch);

        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->postJson('/api/pacientes/pacientes/'.$paciente->id.'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje',
            'version' => 99,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_VERSION_CONFLICT');
    }

    public function test_administracion_can_pause_a_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->postJson('/api/pacientes/pacientes/'.$paciente->id.'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje familiar',
            'version' => $paciente->version,
        ])->assertOk()->assertJsonPath('data.estado', 'en_pausa');
    }

    public function test_recepcion_cannot_change_patient_status(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->postJson('/api/pacientes/pacientes/'.$paciente->id.'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje familiar',
            'version' => $paciente->version,
        ])->assertStatus(403);
    }
}
