<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorPrincipalRequiredException;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorContractTest extends PacientesTestCase
{
    public function test_contract_requires_an_active_principal_tutor(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $principalId = $paciente['tutores'][0]['tutor_id'];
        $contract = app(TutorDirectoryContract::class);

        $this->assertTrue($contract->tieneTutorPrincipal($paciente['id']));
        $this->assertSame($principalId, $contract->tutorPrincipal($paciente['id'])?->id);
        $this->assertSame('Madre', $contract->tutorPrincipal($paciente['id'])?->parentesco);

        Tutor::query()->whereKey($principalId)->update(['activo' => false]);

        $this->assertFalse($contract->tieneTutorPrincipal($paciente['id']));
        $this->assertNull($contract->tutorPrincipal($paciente['id']));

        $this->expectException(TutorPrincipalRequiredException::class);
        $contract->asegurarTutorPrincipal($paciente['id']);
    }

    public function test_changing_to_an_active_principal_restores_the_contract(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $principalId = $paciente['tutores'][0]['tutor_id'];

        Tutor::query()->whereKey($principalId)->update(['activo' => false]);

        $secondary = $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Tutor Secundario',
            'ci' => '88888888',
            'telefono' => '70001111',
            'parentesco_id' => Parentesco::query()->where('nombre', 'Padre')->value('id'),
            'sucursal_registro_id' => $branch->id,
        ])->assertCreated()->json('data');

        $this->postJson('/api/pacientes/pacientes/'.$paciente['id'].'/tutores', [
            'tutor_id' => $secondary['id'],
            'es_principal' => false,
        ])->assertCreated();

        $this->postJson(
            '/api/pacientes/pacientes/'.$paciente['id'].'/tutores/'.$secondary['id'].'/principal',
            ['motivo' => 'El tutor anterior fue desactivado.'],
        )->assertOk();

        $contract = app(TutorDirectoryContract::class);
        $contract->asegurarTutorPrincipal($paciente['id']);

        $this->assertTrue($contract->tieneTutorPrincipal($paciente['id']));
        $this->assertSame($secondary['id'], $contract->tutorPrincipal($paciente['id'])?->id);
        $this->assertCount(2, $contract->tutoresDePaciente($paciente['id']));

        $this->assertDatabaseHas('paciente_tutor', [
            'paciente_id' => $paciente['id'],
            'tutor_id' => $secondary['id'],
            'es_principal' => true,
        ]);
        $this->assertDatabaseHas('paciente_tutor', [
            'paciente_id' => $paciente['id'],
            'tutor_id' => $principalId,
            'es_principal' => false,
        ]);
    }

    public function test_contract_exposes_patient_relation_parentesco(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $madre = Parentesco::query()->where('nombre', 'Madre')->firstOrFail();
        $tia = Parentesco::query()->where('nombre', 'Tía')->firstOrFail();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $tutorId = $paciente['tutores'][0]['tutor_id'];

        \DB::table('paciente_tutor')
            ->where('paciente_id', $paciente['id'])
            ->where('tutor_id', $tutorId)
            ->update(['parentesco_id' => $tia->id]);

        $contract = app(TutorDirectoryContract::class);
        $resumen = $contract->tutorPrincipal($paciente['id']);

        $this->assertSame($tia->id, $resumen?->parentescoId);
        $this->assertSame('Tía', $resumen?->parentesco);
        $this->assertNotSame($madre->id, $resumen?->parentescoId);
    }
}
