<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Parentesco;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorAuthorizationTest extends PacientesTestCase
{
    public function test_administracion_can_view_and_edit_contact_but_not_create_or_change_status(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $tutor = $this->createTutor($branch->id);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->getJson('/api/pacientes/tutores')->assertOk();
        $this->getJson('/api/pacientes/tutores/'.$tutor['id'])->assertOk();

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'telefono' => '70002222',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.telefono', '70002222');

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'nombre_completo' => 'Otro Nombre',
            'version' => 2,
        ])->assertStatus(403)
            ->assertJsonPath('code', 'TUTOR_IDENTITY_FIELDS_FORBIDDEN');

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Nuevo Tutor',
            'telefono' => '71234567',
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branch->id,
        ])->assertStatus(403);

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => false,
            'motivo' => 'Baja',
            'version' => 2,
        ])->assertStatus(403);

        $this->deleteJson('/api/pacientes/tutores/'.$tutor['id'])->assertStatus(403);
    }

    public function test_recepcion_can_create_edit_and_associate_but_not_change_status(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $tutor = $this->createTutor($branch->id);

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'nombre_completo' => 'Ana María López',
            'version' => 1,
        ])->assertOk();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/pacientes/pacientes/'.$paciente['id'].'/tutores', [
            'tutor_id' => $tutor['id'],
            'parentesco_id' => $this->parentescoId('Tía'),
            'es_principal' => false,
        ])->assertCreated()
            ->assertJsonPath('data.parentesco', 'Tía');

        $this->postJson('/api/pacientes/tutores/'.$tutor['id'].'/estado', [
            'activo' => false,
            'motivo' => 'Baja',
            'version' => 2,
        ])->assertStatus(403);
    }

    public function test_scoped_user_cannot_see_or_edit_tutor_from_another_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branchA);
        $tutorB = $this->createTutor($branchB->id);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->getJson('/api/pacientes/tutores/'.$tutorB['id'])
            ->assertStatus(404)
            ->assertJsonPath('code', 'TUTOR_NOT_FOUND');

        $ids = collect($this->getJson('/api/pacientes/tutores?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertNotContains($tutorB['id'], $ids);

        $this->patchJson('/api/pacientes/tutores/'.$tutorB['id'], [
            'telefono' => '70003333',
            'version' => 1,
        ])->assertStatus(404);
    }

    public function test_shared_tutor_identity_is_only_editable_with_all_branches_in_scope(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branchA);
        $tutor = $this->createTutor($branchA->id);

        $pacienteB = $this->paciente(['sucursal_registro_id' => $branchB->id]);
        PacienteTutor::query()->create([
            'paciente_id' => $pacienteB->id,
            'tutor_id' => $tutor['id'],
            'parentesco_id' => $this->parentescoId('Madre'),
            'es_principal' => true,
        ]);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'nombre_completo' => 'Nombre Cambiado',
            'version' => 1,
        ])->assertStatus(403)
            ->assertJsonPath('code', 'TUTOR_IDENTITY_OUT_OF_SCOPE');

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'telefono' => '70004444',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.telefono', '70004444');
    }

    public function test_tutor_create_requires_a_branch_for_global_scope(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Tutor Sin Sucursal',
            'telefono' => '71234567',
            'parentesco_id' => $this->parentescoId('Madre'),
        ])->assertStatus(422)
            ->assertJsonPath('code', 'TUTOR_SUCURSAL_REQUIRED');

        $branch = $this->branch();

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Tutor Con Sucursal',
            'telefono' => '71234567',
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branch->id,
        ])->assertCreated()
            ->assertJsonPath('data.sucursal_registro.id', $branch->id);
    }

    private function parentescoId(string $name): int
    {
        return (int) Parentesco::query()->where('nombre', $name)->value('id');
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
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branchId,
        ], $overrides))->assertCreated()->json('data');
    }
}
