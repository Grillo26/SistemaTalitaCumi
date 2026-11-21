<?php

namespace Tests\Feature\Modules\Profesionales\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfesionalSucursalTest extends ProfesionalesTestCase
{
    public function test_superadmin_assigns_branch_and_cannot_duplicate_it(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branch->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.sucursal_id', $branch->id)
            ->assertJsonPath('data.activo', true);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branch->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_SUCURSAL_DUPLICATED');
    }

    public function test_inactive_relation_must_be_activated_not_recreated(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch, false);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branch->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_SUCURSAL_DUPLICATED');

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales/{$branch->id}/activar", [
            'motivo' => 'Reincorporación',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 2);
    }

    public function test_deactivate_relation_requires_reason_and_bumps_version(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Cambio de sede',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.motivo', 'Cambio de sede')
            ->assertJsonPath('data.version', 2);
    }

    public function test_relation_version_conflict_is_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Cambio de sede',
            'version' => 9,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_SUCURSAL_VERSION_CONFLICT');
    }

    public function test_inactive_branch_cannot_be_assigned(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch(['activo' => false]);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branch->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_INACTIVE');
    }

    public function test_delete_relation_is_always_rejected_with_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch);

        $this->deleteJson("/api/profesionales/profesionales/{$profesional->id}/sucursales/{$branch->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_SUCURSAL_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('profesional_sucursal', [
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
        ]);
    }

    public function test_non_global_user_cannot_assign_a_branch_outside_scope(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branchA);

        /** @var User $user */
        $user = $this->userWithRole('administracion');
        $user->givePermissionTo(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN);
        $this->withBranch($user, $branchA);
        Sanctum::actingAs($user);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branchB->id,
        ])->assertForbidden();

        // La sucursal accesible ya está asignada: el duplicado se rechaza.
        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/sucursales", [
            'sucursal_id' => $branchA->id,
        ])->assertStatus(409);
    }

    public function test_branch_list_only_shows_accessible_branches(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branchA);
        $this->assignBranch($profesional, $branchB);

        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branchA);

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}/sucursales")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $branchA->id)
            ->assertJsonPath('can.administrar', false);
    }

    public function test_relation_list_reports_state_and_version(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();
        $branch = $this->branch();
        $relation = $this->assignBranch($profesional, $branch);
        $relation->forceFill(['activo' => false, 'motivo' => 'Cambio', 'version' => 2])->save();

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}/sucursales")
            ->assertOk()
            ->assertJsonPath('data.0.activo_relacion', false)
            ->assertJsonPath('data.0.motivo', 'Cambio')
            ->assertJsonPath('data.0.version', 2);
    }
}
