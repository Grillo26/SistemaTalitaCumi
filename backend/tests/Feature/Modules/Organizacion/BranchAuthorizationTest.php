<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use Tests\Support\FakeUserBranchAccessContract;

/**
 * Matriz de permisos del submódulo (RF-014 / CA-008), sin nombrar roles en
 * el código de aplicación.
 */
final class BranchAuthorizationTest extends OrganizacionTestCase
{
    public function test_reception_can_view_but_cannot_write(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $user = $this->actingAsRole('recepcion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson('/api/organizacion/sucursales')->assertOk();
        $this->getJson("/api/organizacion/sucursales/{$branch->id}")->assertOk();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-999',
            'nombre' => 'Prohibida',
        ])->assertForbidden();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])->assertForbidden();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Prohibido',
            'version' => 1,
        ])->assertForbidden();

        $this->deleteJson("/api/organizacion/sucursales/{$branch->id}")->assertForbidden();
    }

    public function test_administracion_can_edit_contact_but_not_structure_or_status(): void
    {
        $branch = Branch::factory()->create(['version' => 1]);

        $user = $this->actingAsRole('administracion');

        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = [$branch->id];
        $this->app->instance(UserBranchAccessContract::class, $access);

        $this->getJson('/api/organizacion/sucursales')->assertOk();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-999',
            'nombre' => 'Prohibida',
        ])->assertForbidden();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])->assertOk();

        $this->patchJson("/api/organizacion/sucursales/{$branch->id}", [
            'nombre' => 'Nombre prohibido',
            'version' => 2,
        ])->assertForbidden();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/activar", [
            'motivo' => 'Prohibido',
            'version' => 2,
        ])->assertForbidden();

        $this->deleteJson("/api/organizacion/sucursales/{$branch->id}")->assertForbidden();
    }
}
