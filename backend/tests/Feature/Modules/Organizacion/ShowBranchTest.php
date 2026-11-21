<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use Tests\Support\FakeUserBranchAccessContract;

final class ShowBranchTest extends OrganizacionTestCase
{
    public function test_superadmin_can_view_branch_detail(): void
    {
        $branch = Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'telefono' => '70000000',
        ]);

        $this->actingAsSuperadmin();

        $this->getJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.codigo', 'SUC-001')
            ->assertJsonPath('data.telefono', '70000000')
            ->assertJsonPath('can.update', true);
    }

    public function test_missing_branch_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/sucursales/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'BRANCH_NOT_FOUND');
    }

    public function test_reception_can_view_only_assigned_branch_with_contract(): void
    {
        $assigned = Branch::factory()->create();
        $other = Branch::factory()->create();

        $user = $this->actingAsRole('recepcion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$assigned->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson("/api/organizacion/sucursales/{$assigned->id}")
            ->assertOk()
            ->assertJsonPath('can.update', false)
            ->assertJsonPath('can.change_status', false);

        $this->getJson("/api/organizacion/sucursales/{$other->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_reception_without_assigned_branches_cannot_view_branch(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAsRole('recepcion');

        $this->getJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_administracion_sees_contact_permission_in_detail(): void
    {
        $branch = Branch::factory()->create();

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertOk()
            ->assertJsonPath('can.update', false)
            ->assertJsonPath('can.update_contact', true)
            ->assertJsonPath('can.change_status', false);
    }
}
