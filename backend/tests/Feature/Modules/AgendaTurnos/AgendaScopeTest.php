<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\Organizacion\Support\BranchPermission;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

final class AgendaScopeTest extends AgendaSemanalTestCase
{
    public function test_selector_only_returns_assigned_branches_and_defaults_to_first_by_name(): void
    {
        $branchA = $this->branch(['nombre' => 'Zeta']);
        $branchB = $this->branch(['nombre' => 'Alfa']);
        $branchC = $this->branch(['nombre' => 'No asignada']);
        $user = $this->actingAsWithPermissions(
            ['turno.ver'],
            [$branchA->id, $branchB->id],
        );

        $response = $this->getJson('/api/agenda-turnos/agenda-semanal/sucursales');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$branchA->id, $branchB->id], $ids);
        $this->assertNotContains($branchC->id, $ids);
        $this->assertSame($branchB->id, $response->json('predeterminada_id'));
        $this->assertNotNull($user);
    }

    public function test_direct_request_for_another_branch_is_rejected(): void
    {
        $scenario = $this->scenario();
        $otherBranch = $this->branch(['nombre' => 'Otra']);

        $this->getJson($this->weekUrl($otherBranch->id))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_user_without_branches_gets_a_clear_error(): void
    {
        $this->actingAsWithPermissions(['turno.ver'], []);

        $this->getJson('/api/agenda-turnos/agenda-semanal/sucursales')
            ->assertStatus(403)
            ->assertJsonPath('code', 'SIN_SUCURSALES_ASIGNADAS')
            ->assertJsonPath(
                'message',
                'No tenés sucursales asignadas. Contactá al Superadmin para que te asigne al menos una.',
            );

        $this->getJson('/api/agenda-turnos/agenda-semanal')
            ->assertStatus(403)
            ->assertJsonPath('code', 'SIN_SUCURSALES_ASIGNADAS');
    }

    public function test_global_scope_user_sees_all_active_branches(): void
    {
        $branchA = $this->branch(['nombre' => 'Centro']);
        $branchB = $this->branch(['nombre' => 'Norte']);
        $user = User::factory()->create();
        $user->givePermissionTo(['turno.ver', BranchPermission::GLOBAL_SCOPE]);
        Sanctum::actingAs($user);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->getJson('/api/agenda-turnos/agenda-semanal/sucursales');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($branchA->id, $ids);
        $this->assertContains($branchB->id, $ids);
    }
}
