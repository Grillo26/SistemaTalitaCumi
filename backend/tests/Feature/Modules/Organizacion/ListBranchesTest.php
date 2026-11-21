<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use Tests\Support\FakeUserBranchAccessContract;

final class ListBranchesTest extends OrganizacionTestCase
{
    public function test_superadmin_lists_and_searches_by_code_and_name(): void
    {
        Branch::factory()->create(['codigo' => 'SUC-001', 'nombre' => 'Sucursal Central', 'activo' => true]);
        Branch::factory()->create(['codigo' => 'SUC-002', 'nombre' => 'Sucursal Norte', 'activo' => false]);

        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/sucursales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('can.create', true)
            ->assertJsonPath('can.change_status', true);

        $this->getJson('/api/organizacion/sucursales?search=SUC-001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'SUC-001');

        $this->getJson('/api/organizacion/sucursales?search=Central')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'SUC-001');
    }

    public function test_superadmin_filters_by_status_and_paginates(): void
    {
        Branch::factory()->create(['codigo' => 'SUC-001', 'nombre' => 'Central', 'activo' => true]);
        Branch::factory()->create(['codigo' => 'SUC-002', 'nombre' => 'Norte', 'activo' => false]);

        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/sucursales?estado=inactiva')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'SUC-002');

        $this->getJson('/api/organizacion/sucursales?per_page=1&page=2&sort=codigo&direction=asc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.codigo', 'SUC-002');
    }

    public function test_reception_sees_only_assigned_branches_with_contract(): void
    {
        $assigned = Branch::factory()->create(['codigo' => 'SUC-001', 'nombre' => 'Central']);
        Branch::factory()->create(['codigo' => 'SUC-002', 'nombre' => 'Norte']);

        $user = $this->actingAsRole('recepcion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$assigned->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson('/api/organizacion/sucursales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'SUC-001')
            ->assertJsonPath('can.create', false)
            ->assertJsonPath('can.update', false)
            ->assertJsonPath('can.update_contact', false)
            ->assertJsonPath('can.change_status', false);
    }

    public function test_reception_with_real_contract_sees_only_assigned_branches(): void
    {
        $assigned = Branch::factory()->create(['codigo' => 'SUC-100', 'nombre' => 'Asignada']);
        Branch::factory()->create(['codigo' => 'SUC-200', 'nombre' => 'No asignada']);

        $user = $this->actingAsRole('recepcion');
        $user->branches()->attach($assigned->id);

        $this->getJson('/api/organizacion/sucursales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'SUC-100')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_reception_without_assigned_branches_sees_empty_list(): void
    {
        Branch::factory()->create();

        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/sucursales')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('can.create', false)
            ->assertJsonPath('can.update', false);
    }

    public function test_administracion_can_list_with_contract_but_cannot_edit_structure(): void
    {
        $branch = Branch::factory()->create(['codigo' => 'SUC-001', 'nombre' => 'Central']);

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson('/api/organizacion/sucursales')
            ->assertOk()
            ->assertJsonPath('can.create', false)
            ->assertJsonPath('can.update', false)
            ->assertJsonPath('can.update_contact', true)
            ->assertJsonPath('can.change_status', false);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/organizacion/sucursales')->assertUnauthorized();
    }

    public function test_unauthenticated_non_json_request_returns_401_instead_of_redirect(): void
    {
        $this->get('/api/organizacion/sucursales')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Sesión no iniciada o token inválido.')
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_per_page_is_limited_to_fifty(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/sucursales?per_page=51')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }
}
