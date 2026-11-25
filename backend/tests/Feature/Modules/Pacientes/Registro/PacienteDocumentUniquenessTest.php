<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteDocumentUniquenessTest extends PacientesTestCase
{
    public function test_document_comparison_ignores_case_and_outer_spaces(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $tutorA = $this->tutor(['sucursal_registro_id' => $branch->id]);
        $tutorB = $this->tutor(['sucursal_registro_id' => $branch->id]);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => 'ABC-1',
            'tutor_principal' => ['modo' => 'existente', 'tutor_id' => $tutorA->id],
        ], $branch))->assertCreated();

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => '  abc-1 ',
            'nombre' => 'Otra',
            'tutor_principal' => ['modo' => 'existente', 'tutor_id' => $tutorB->id],
        ], $branch))
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_DOCUMENT_DUPLICATE');
    }

    public function test_multiple_patients_without_document_are_allowed(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $tutorA = $this->tutor(['sucursal_registro_id' => $branch->id]);
        $tutorB = $this->tutor(['sucursal_registro_id' => $branch->id]);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => null,
            'nombre' => 'Ana',
            'tutor_principal' => ['modo' => 'existente', 'tutor_id' => $tutorA->id],
        ], $branch))->assertCreated();

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => null,
            'nombre' => 'Beto',
            'tutor_principal' => ['modo' => 'existente', 'tutor_id' => $tutorB->id],
        ], $branch))->assertCreated();
    }

    public function test_accents_are_normalized_for_soft_duplicate_detection(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'nombre' => 'José',
            'apellido_paterno' => 'Núñez',
            'apellido_materno' => 'Pérez',
            'fecha_nacimiento' => '2019-03-04',
        ]);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => null,
            'nombre' => 'Jose',
            'apellido_paterno' => 'Nunez',
            'apellido_materno' => 'Perez',
            'fecha_nacimiento' => '2019-03-04',
        ], $branch))
            ->assertStatus(409)
            ->assertJsonPath('code', 'POTENTIAL_DUPLICATE');
    }

    public function test_update_to_duplicate_document_is_rejected(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $this->paciente(['sucursal_registro_id' => $branch->id, 'ci' => '11111111']);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id, 'ci' => '22222222']);

        $this->patchJson('/api/pacientes/pacientes/'.$paciente->id, [
            'ci' => '11111111',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_DOCUMENT_DUPLICATE');
    }
}
