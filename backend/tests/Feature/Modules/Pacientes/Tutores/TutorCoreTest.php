<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorCoreTest extends PacientesTestCase
{
    public function test_create_search_and_update_tutor(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco' => 'Madre',
        ])->assertCreated()->json('data');

        $this->getJson('/api/pacientes/tutores?search=Ana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $created['id']);

        $this->getJson('/api/pacientes/tutores?search=712-34567')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $created['id']);

        $updated = $this->patchJson('/api/pacientes/tutores/'.$created['id'], [
            'telefono' => '70001111',
            'version' => 1,
        ])->assertOk()->json('data');

        $this->assertSame('70001111', $updated['telefono']);
        $this->assertSame(2, $updated['version']);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tutor',
            'entidad_id' => $created['id'],
            'accion' => 'CREAR',
            'modulo' => 'pacientes',
        ]);
    }

    public function test_tutor_duplicate_document_is_rejected(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco' => 'Madre',
        ])->assertCreated();

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Otra Ana',
            'ci' => ' 55555555 ',
            'telefono' => '70000000',
            'parentesco' => 'Tía',
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_DUPLICATE');
    }

    public function test_existing_tutor_can_be_used_in_patient_create(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $tutor = $this->tutor([
            'nombre_completo' => 'Carlos Pérez',
            'sucursal_registro_id' => $branch->id,
        ]);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'tutor_principal' => ['modo' => 'existente', 'tutor_id' => $tutor->id],
        ], $branch))->assertCreated()->json('data');

        $this->assertSame($tutor->id, $created['tutores'][0]['tutor_id']);
        $this->assertSame(1, Tutor::query()->count());
        $this->assertDatabaseHas('paciente_tutor', [
            'paciente_id' => $created['id'],
            'tutor_id' => $tutor->id,
            'es_principal' => true,
        ]);
    }

    public function test_principal_tutor_cannot_be_dissociated_from_active_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $tutorId = $created['tutores'][0]['tutor_id'];

        $this->deleteJson('/api/pacientes/pacientes/'.$created['id'].'/tutores/'.$tutorId)
            ->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_PRINCIPAL_REQUIRED');
    }

    public function test_changing_principal_and_dissociating_secondary_tutor(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $principalId = $created['tutores'][0]['tutor_id'];
        $secondary = $this->tutor([
            'nombre_completo' => 'Segundo Tutor',
            'sucursal_registro_id' => $branch->id,
        ]);

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/tutores', [
            'tutor_id' => $secondary->id,
            'es_principal' => false,
        ])->assertCreated();

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/tutores/'.$secondary->id.'/principal', [
            'motivo' => 'La madre solicitó el cambio de tutor principal.',
        ])->assertOk()
            ->assertJsonPath('data.es_principal', true);

        $this->assertFalse(
            (bool) PacienteTutor::query()
                ->where('paciente_id', $created['id'])
                ->where('tutor_id', $principalId)
                ->value('es_principal'),
        );

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/tutores/'.$principalId.'/principal', [
            'motivo' => 'Se restituye el tutor principal original.',
        ])->assertOk();

        $this->deleteJson('/api/pacientes/pacientes/'.$created['id'].'/tutores/'.$secondary->id)
            ->assertOk();

        $this->assertDatabaseMissing('paciente_tutor', [
            'paciente_id' => $created['id'],
            'tutor_id' => $secondary->id,
        ]);
    }

    public function test_tutor_directory_contract(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $contract = app(TutorDirectoryContract::class);
        $paciente = Paciente::query()->findOrFail($created['id']);

        $this->assertTrue($contract->tieneTutorPrincipal($paciente->id));
        $this->assertNotNull($contract->tutorPrincipal($paciente->id));
        $this->assertCount(1, $contract->tutoresDePaciente($paciente->id));

        $contract->asegurarTutorPrincipal($paciente->id);
        $this->assertTrue(true);
    }

    public function test_tutor_visibility_by_patient_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $pacienteA = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $pacienteB = $this->paciente(['sucursal_registro_id' => $branchB->id]);

        $tutorA = $this->tutor(['nombre_completo' => 'Tutor Sucursal A']);
        $tutorB = $this->tutor(['nombre_completo' => 'Tutor Sucursal B']);
        $tutorSinPacientes = $this->tutor([
            'nombre_completo' => 'Tutor Sin Pacientes',
            'sucursal_registro_id' => $branchA->id,
        ]);

        PacienteTutor::query()->create([
            'paciente_id' => $pacienteA->id,
            'tutor_id' => $tutorA->id,
            'es_principal' => true,
        ]);
        PacienteTutor::query()->create([
            'paciente_id' => $pacienteB->id,
            'tutor_id' => $tutorB->id,
            'es_principal' => true,
        ]);

        $ids = collect($this->getJson('/api/pacientes/tutores?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($tutorA->id, $ids);
        $this->assertContains($tutorSinPacientes->id, $ids);
        $this->assertNotContains($tutorB->id, $ids);
    }

    public function test_branch_filter_uses_the_tutor_home_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $pacienteA = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $tutorRegistradoEnB = $this->tutor([
            'nombre_completo' => 'Tutor Registrado en B',
            'sucursal_registro_id' => $branchB->id,
        ]);

        PacienteTutor::query()->create([
            'paciente_id' => $pacienteA->id,
            'tutor_id' => $tutorRegistradoEnB->id,
            'es_principal' => true,
        ]);

        $sinFiltro = collect($this->getJson('/api/pacientes/tutores?per_page=50')->assertOk()->json('data'))
            ->pluck('id')
            ->all();
        $this->assertContains($tutorRegistradoEnB->id, $sinFiltro);

        $filtradoA = collect(
            $this->getJson('/api/pacientes/tutores?sucursal_registro_id='.$branchA->id.'&per_page=50')
                ->assertOk()
                ->json('data'),
        )->pluck('id')->all();

        $this->assertNotContains($tutorRegistradoEnB->id, $filtradoA);
    }
}
