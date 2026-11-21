<?php

namespace Tests\Feature\Modules\Profesionales\Directorio;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfesionalListTest extends ProfesionalesTestCase
{
    public function test_superadmin_sees_all_professionals(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $a = $this->profesional(['nombre_completo' => 'Profesional A']);
        $b = $this->profesional(['nombre_completo' => 'Profesional B']);
        $this->assignBranch($a, $branchA);
        $this->assignBranch($b, $branchB);

        $this->actingAsSuperadmin();

        $this->getJson('/api/profesionales/profesionales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_only_sees_professionals_of_accessible_branches(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $a = $this->profesional(['nombre_completo' => 'Profesional A']);
        $b = $this->profesional(['nombre_completo' => 'Profesional B']);
        $this->assignBranch($a, $branchA);
        $this->assignBranch($b, $branchB);

        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branchA);

        $this->getJson('/api/profesionales/profesionales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre_completo', 'Profesional A');

        $this->getJson("/api/profesionales/profesionales/{$b->id}")
            ->assertForbidden();

        $this->getJson("/api/profesionales/profesionales?sucursal_id={$branchB->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_without_branches_sees_empty_list_fail_closed(): void
    {
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $this->branch());

        $this->actingAsRole('administracion');

        $this->getJson('/api/profesionales/profesionales')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_professional_without_branch_is_only_visible_to_global_scope(): void
    {
        $profesional = $this->profesional(['nombre_completo' => 'Sin Sucursal']);

        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $this->getJson('/api/profesionales/profesionales')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertForbidden();

        $this->actingAsSuperadmin();
        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertOk()
            ->assertJsonPath('data.asignable', false)
            ->assertJsonPath('data.motivos_no_asignable.0.codigo', 'SIN_SUCURSAL_ACTIVA');
    }

    public function test_filters_by_search_specialty_branch_and_state(): void
    {
        $this->actingAsSuperadmin();

        $branch = $this->branch();
        $psicologia = $this->especialidad('Psicología');
        $fisioterapia = Especialidad::factory()->create(['nombre' => 'Fisioterapia Filtro']);

        $a = $this->profesional(['nombre_completo' => 'Ana Filtro'], $psicologia);
        $b = $this->profesional(['nombre_completo' => 'Beatriz Filtro'], $fisioterapia);
        $this->assignBranch($a, $branch);

        $this->getJson('/api/profesionales/profesionales?search=Ana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre_completo', 'Ana Filtro');

        $this->getJson("/api/profesionales/profesionales?especialidad_id={$fisioterapia->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $b->id);

        $this->getJson("/api/profesionales/profesionales?sucursal_id={$branch->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $a->id);

        $a->forceFill(['activo' => false])->save();

        $this->getJson('/api/profesionales/profesionales?estado=inactivo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $a->id);
    }

    public function test_assignability_turns_true_with_active_specialty_and_branch(): void
    {
        $this->actingAsSuperadmin();

        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertOk()
            ->assertJsonPath('data.asignable', true)
            ->assertJsonPath('data.motivos_no_asignable', []);
    }

    public function test_inactive_professional_is_visible_but_not_assignable(): void
    {
        $this->actingAsSuperadmin();

        $branch = $this->branch();
        $profesional = $this->profesional(['activo' => false]);
        $this->assignBranch($profesional, $branch);

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.asignable', false)
            ->assertJsonPath('data.motivos_no_asignable.0.codigo', 'PROFESIONAL_INACTIVO');
    }

    public function test_secondary_specialties_are_represented_when_present(): void
    {
        $this->actingAsSuperadmin();

        $principal = $this->especialidad('Psicología');
        $secondary = Especialidad::factory()->create(['nombre' => 'Secundaria Test']);
        $profesional = $this->profesional([], $principal);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $secondary->id,
            'tipo' => TipoEspecialidad::SECUNDARIA,
            'activo' => true,
        ]);

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.especialidades');
    }
}
