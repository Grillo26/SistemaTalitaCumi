<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Branch;

final class DeleteBranchTest extends OrganizacionTestCase
{
    public function test_superadmin_cannot_delete_branch_and_is_offered_deactivation(): void
    {
        $branch = Branch::factory()->create(['nombre' => 'Sucursal Central']);

        $this->actingAsSuperadmin();

        $this->deleteJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_DELETE_NOT_ALLOWED')
            ->assertJsonPath(
                'message',
                'No se puede eliminar la sucursal "Sucursal Central". Usá la acción Desactivar para conservar el historial.',
            );

        $this->assertDatabaseHas('sucursal', ['id' => $branch->id]);
        $this->assertSame(0, BitacoraEntry::query()->where('entidad', 'sucursal')->count());
    }

    public function test_reception_cannot_attempt_deletion(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAsRole('recepcion');

        $this->deleteJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertForbidden();
    }

    public function test_administracion_cannot_attempt_deletion(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAsRole('administracion');

        $this->deleteJson("/api/organizacion/sucursales/{$branch->id}")
            ->assertForbidden();
    }

    public function test_missing_branch_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->deleteJson('/api/organizacion/sucursales/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'BRANCH_NOT_FOUND');
    }
}
