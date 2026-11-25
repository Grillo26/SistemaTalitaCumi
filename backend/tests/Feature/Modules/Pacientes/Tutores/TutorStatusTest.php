<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Parentesco;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorStatusTest extends PacientesTestCase
{
    public function test_deactivation_requires_reason_and_same_state_is_rejected(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id, ['telefono' => '71234567']);

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => false,
            'version' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('code', 'STATUS_REASON_REQUIRED');

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => true,
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_STATUS_UNCHANGED');
    }

    public function test_deactivation_and_reactivation_are_audited_with_full_values(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id, [
            'ci' => '7654321',
            'telefono' => '71234567',
            'email' => 'ana@example.test',
            'direccion' => 'Av. Siempre Viva 123',
        ]);

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => false,
            'motivo' => 'La familia pidió no ser contactada.',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.estado', 'inactivo')
            ->assertJsonPath('data.motivo_estado', 'La familia pidió no ser contactada.');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'tutor')
            ->where('entidad_id', $tutor['id'])
            ->where('accion', 'CAMBIAR_ESTADO')
            ->firstOrFail();

        $this->assertTrue((bool) $audit->critico);
        $this->assertSame('La familia pidió no ser contactada.', $audit->motivo);
        $this->assertSame('71234567', $audit->datos_anteriores['telefono']);
        $this->assertSame('71234567', $audit->datos_nuevos['telefono']);
        $this->assertSame('7654321', $audit->datos_nuevos['ci']);
        $this->assertSame('Av. Siempre Viva 123', $audit->datos_nuevos['direccion']);

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => true,
            'version' => 2,
        ])->assertOk()
            ->assertJsonPath('data.activo', true);

        $this->assertSame(
            1,
            BitacoraEntry::query()
                ->where('entidad', 'tutor')
                ->where('entidad_id', $tutor['id'])
                ->where('accion', 'CAMBIAR_ESTADO')
                ->where('critico', false)
                ->count(),
        );
    }

    public function test_principal_of_an_active_patient_cannot_be_deactivated(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $tutorId = $paciente['tutores'][0]['tutor_id'];

        $this->postJson('/api/pacientes/tutores/'.$tutorId.'/estado', [
            'activo' => false,
            'motivo' => 'Baja',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_PRINCIPAL_ACTIVE_PATIENT');
    }

    public function test_inactive_tutor_cannot_be_associated_nor_made_principal(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $secondary = $this->createTutor($branch->id, [
            'nombre_completo' => 'Tutor Secundario',
            'ci' => '88888888',
            'telefono' => '70001111',
        ]);

        $this->postJson('/api/pacientes/tutores/'.$secondary['id'].'/estado', [
            'activo' => false,
            'motivo' => 'Ya no es contacto familiar.',
            'version' => 1,
        ])->assertOk();

        $this->postJson('/api/pacientes/pacientes/'.$paciente['id'].'/tutores', [
            'tutor_id' => $secondary['id'],
            'es_principal' => false,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_INACTIVE');

        $this->postJson(
            '/api/pacientes/pacientes/'.$paciente['id'].'/tutores/'.$secondary['id'].'/principal',
            ['motivo' => 'Cambio'],
        )->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_INACTIVE');
    }

    public function test_delete_is_always_rejected_for_authorized_users(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $tutor = $this->createTutor($branch->id);

        $this->deleteJson('/api/pacientes/tutores/'.$tutor['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('tutor', ['id' => $tutor['id']]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function createTutor(int $branchId, array $overrides = []): array
    {
        return $this->postJson('/api/pacientes/tutores', array_merge([
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco_id' => Parentesco::query()->where('nombre', 'Madre')->value('id'),
            'sucursal_registro_id' => $branchId,
        ], $overrides))->assertCreated()->json('data');
    }
}
