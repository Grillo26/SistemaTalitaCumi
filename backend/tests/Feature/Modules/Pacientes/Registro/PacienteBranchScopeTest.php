<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteBranchScopeTest extends PacientesTestCase
{
    public function test_scoped_user_only_sees_patients_of_their_branches(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $pacienteA = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $pacienteB = $this->paciente(['sucursal_registro_id' => $branchB->id]);

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pacienteA->id);

        $this->getJson('/api/pacientes/pacientes/'.$pacienteB->id)
            ->assertStatus(404)
            ->assertJsonPath('code', 'PATIENT_NOT_FOUND');
    }

    public function test_user_without_branches_sees_nothing_and_cannot_create(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_create_with_branch_outside_scope_is_forbidden(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branchB))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_global_scope_sees_all_patients(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $this->actingAsSuperadmin();

        $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $this->paciente(['sucursal_registro_id' => $branchB->id]);

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
