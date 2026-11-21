<?php

namespace Tests\Feature\Modules\Profesionales\Matriz;

use App\Models\User;
use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class MatrizTest extends ProfesionalesTestCase
{
    public function test_superadmin_sees_professional_specialty_branch_matrix(): void
    {
        $this->actingAsSuperadmin();
        [$profesional, $principal, $secundaria] = $this->professionalWithTwoSpecialties();
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->assignBranch($profesional, $branchA);
        $this->assignBranch($profesional, $branchB);

        $response = $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('data.0.profesional.id', $profesional->id)
            ->assertJsonPath('data.0.asignable', true);

        $rows = collect($response->json('data'));
        $this->assertCount(4, $rows);
        $this->assertCount(2, $rows->where('especialidad.id', $principal->id));
        $this->assertCount(2, $rows->where('especialidad.id', $secundaria->id));
        $this->assertCount(2, $rows->where('sucursal.id', $branchA->id));
        $this->assertCount(2, $rows->where('sucursal.id', $branchB->id));
    }

    public function test_filters_by_professional_specialty_branch_search_and_state(): void
    {
        $this->actingAsSuperadmin();
        [$profesional, $principal, $secundaria] = $this->professionalWithTwoSpecialties();
        $branchA = $this->branch(['nombre' => 'Sucursal Central']);
        $branchB = $this->branch(['nombre' => 'Sucursal Norte']);
        $this->assignBranch($profesional, $branchA);
        $this->assignBranch($profesional, $branchB);

        $this->getJson("/api/profesionales/matriz?profesional_id={$profesional->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 4);

        $this->getJson("/api/profesionales/matriz?especialidad_id={$secundaria->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/profesionales/matriz?sucursal_id={$branchA->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/profesionales/matriz?search=Norte')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.sucursal.nombre', 'Sucursal Norte');

        $profesional->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/matriz?estado=inactivo')
            ->assertOk()
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('data.0.asignable', false);

        $this->getJson('/api/profesionales/matriz?solo_asignables=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_matrix_only_includes_active_relations(): void
    {
        $this->actingAsSuperadmin();
        [$profesional, $principal] = $this->professionalWithTwoSpecialties();
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->assignBranch($profesional, $branchA);
        $relationB = $this->assignBranch($profesional, $branchB);

        $relationB->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissing(['sucursal' => ['id' => $branchB->id]]);

        $secondary = ProfesionalEspecialidad::query()
            ->where('profesional_id', $profesional->id)
            ->where('tipo', TipoEspecialidad::SECUNDARIA->value)
            ->firstOrFail();
        $secondary->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.especialidad.id', $principal->id);
    }

    public function test_inactive_professional_specialty_and_branch_are_not_assignable(): void
    {
        $this->actingAsSuperadmin();
        [$profesional] = $this->professionalWithTwoSpecialties();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch);

        $profesional->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/matriz?estado=inactivo')
            ->assertOk()
            ->assertJsonPath('data.0.asignable', false)
            ->assertJsonPath('data.0.motivos_no_asignable.0.codigo', 'PROFESIONAL_INACTIVO');

        $profesional->forceFill(['activo' => true])->save();
        $especialidad = Especialidad::factory()->create(['activo' => false]);
        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $especialidad->id,
            'tipo' => 'secundaria',
            'activo' => true,
        ]);

        $this->getJson("/api/profesionales/matriz?especialidad_id={$especialidad->id}")
            ->assertOk()
            ->assertJsonPath('data.0.asignable', false)
            ->assertJsonPath('data.0.motivos_no_asignable.0.codigo', 'ESPECIALIDAD_INACTIVA');

        $branch->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('data.0.sucursal.activo', false)
            ->assertJsonPath('data.0.asignable', false);
    }

    public function test_matrix_is_scoped_by_accessible_branches(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        [$profesional] = $this->professionalWithTwoSpecialties();
        $this->assignBranch($profesional, $branchA);
        $this->assignBranch($profesional, $branchB);

        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branchA);

        $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/profesionales/matriz?sucursal_id={$branchB->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/profesionales/matriz/opciones')
            ->assertOk()
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonPath('data.sucursales.0.id', $branchA->id)
            ->assertJsonCount(1, 'data.profesionales');
    }

    public function test_user_without_branches_sees_empty_matrix_fail_closed(): void
    {
        [$profesional] = $this->professionalWithTwoSpecialties();
        $this->assignBranch($profesional, $this->branch());

        $this->actingAsRole('administracion');

        $this->getJson('/api/profesionales/matriz')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/profesionales/matriz/opciones')
            ->assertOk()
            ->assertJsonCount(0, 'data.sucursales')
            ->assertJsonCount(0, 'data.profesionales');
    }

    public function test_recepcion_can_view_matrix_but_user_without_permission_cannot(): void
    {
        [$profesional] = $this->professionalWithTwoSpecialties();
        $this->assignBranch($profesional, $this->branch());

        $this->actingAsRole('recepcion');
        $this->getJson('/api/profesionales/matriz')->assertOk();

        /** @var User $user */
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/profesionales/matriz')->assertForbidden();
    }

    public function test_matrix_is_paginated(): void
    {
        $this->actingAsSuperadmin();
        [$profesional] = $this->professionalWithTwoSpecialties();
        $this->assignBranch($profesional, $this->branch());
        $this->assignBranch($profesional, $this->branch());

        $this->getJson('/api/profesionales/matriz?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 4);
    }

    /**
     * @return array{Profesional, Especialidad, Especialidad}
     */
    private function professionalWithTwoSpecialties(): array
    {
        $principal = $this->especialidad();
        $secundaria = Especialidad::factory()->create();
        $profesional = $this->profesional([], $principal);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $secundaria->id,
            'tipo' => TipoEspecialidad::SECUNDARIA,
            'activo' => true,
        ]);

        return [$profesional, $principal, $secundaria];
    }
}
