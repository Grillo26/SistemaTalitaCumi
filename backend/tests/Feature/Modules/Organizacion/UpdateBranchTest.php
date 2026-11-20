<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Branch;
use Tests\Support\FakeUserBranchAccessContract;

final class UpdateBranchTest extends OrganizacionTestCase
{
    public function test_superadmin_updates_contact_data_and_audits_before_and_after(): void
    {
        $branch = Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'telefono' => '70000000',
            'version' => 1,
        ]);

        $user = $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.telefono', '71111111')
            ->assertJsonPath('data.version', 2);

        $audit = BitacoraEntry::query()->where('accion', 'ACTUALIZAR')->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('70000000', $audit->datos_anteriores['telefono']);
        $this->assertSame('71111111', $audit->datos_nuevos['telefono']);
    }

    public function test_superadmin_can_update_structural_fields(): void
    {
        $branch = Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'version' => 1,
        ]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'codigo' => ' suc-010 ',
            'nombre' => 'Sucursal  Central  Renovada',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.codigo', 'SUC-010')
            ->assertJsonPath('data.nombre', 'Sucursal Central Renovada')
            ->assertJsonPath('data.version', 2);
    }

    public function test_administracion_can_update_contact_data_with_contract(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'direccion' => 'Nueva dirección 123',
            'email' => 'sucursal@example.com',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.direccion', 'Nueva dirección 123')
            ->assertJsonPath('data.email', 'sucursal@example.com');
    }

    public function test_administracion_cannot_update_structural_fields(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'nombre' => 'Nombre prohibido',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_reception_cannot_update_branch(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $this->actingAsRole('recepcion');

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_rejects_duplicate_code_on_update(): void
    {
        Branch::factory()->create(['codigo' => 'SUC-001', 'nombre' => 'Central']);
        $branch = Branch::factory()->create(['codigo' => 'SUC-002', 'nombre' => 'Norte', 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'codigo' => ' suc-001 ',
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo');
    }

    public function test_rejects_stale_version(): void
    {
        $branch = Branch::factory()->create(['version' => 5]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 4,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_VERSION_CONFLICT');
    }

    public function test_rejects_state_change_through_update_endpoint(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'activo' => false,
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('activo');
    }

    public function test_rejects_update_without_any_field(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo');
    }

    public function test_administracion_cannot_update_branch_outside_scope(): void
    {
        $assigned = Branch::factory()->create(['version' => 1]);
        $other = Branch::factory()->create(['version' => 1]);

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$assigned->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->patchJson("/api/organizacion/sucursales/{$other->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_administracion_without_contract_receives_pending_module_error(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $this->actingAsRole('administracion');

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULE_02_PENDING');
    }

    public function test_no_op_update_does_not_increment_version_or_audit(): void
    {
        $branch = Branch::factory()->create([
            'telefono' => '70000000',
            'version' => 1,
        ]);

        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '70000000',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.version', 1);

        $this->assertSame(0, BitacoraEntry::query()->count());
    }
}
