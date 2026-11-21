<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Branch;

final class BranchStatusTest extends OrganizacionTestCase
{
    public function test_superadmin_deactivates_branch_with_reason_and_audit(): void
    {
        $branch = Branch::factory()->create(['activo' => true, 'version' => 1]);

        $user = $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Cierre temporal por refacción',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.estado', 'inactiva')
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('sucursal', ['id' => $branch->id, 'activo' => false]);

        $audit = BitacoraEntry::query()->where('accion', 'DESACTIVAR')->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('Cierre temporal por refacción', $audit->motivo);
        $this->assertTrue($audit->datos_anteriores['activo']);
        $this->assertFalse($audit->datos_nuevos['activo']);
    }

    public function test_superadmin_activates_branch_with_reason_and_audit(): void
    {
        $branch = Branch::factory()->create(['activo' => false, 'version' => 3]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/activar", [
            'motivo' => 'Reapertura de la sede',
            'version' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 4);

        $audit = BitacoraEntry::query()->where('accion', 'ACTIVAR')->sole();

        $this->assertSame('Reapertura de la sede', $audit->motivo);
    }

    public function test_reason_is_required_for_deactivation(): void
    {
        $branch = Branch::factory()->create(['activo' => true, 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_reason_is_required_for_activation_too(): void
    {
        $branch = Branch::factory()->create(['activo' => false, 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/activar", [
            'motivo' => '   ',
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_rejects_stale_version(): void
    {
        $branch = Branch::factory()->create(['activo' => true, 'version' => 2]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Cierre',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_VERSION_CONFLICT');
    }

    public function test_rejects_change_to_same_status(): void
    {
        $branch = Branch::factory()->create(['activo' => false, 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Ya estaba inactiva',
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'BRANCH_STATUS_UNCHANGED');
    }

    public function test_reception_cannot_change_status(): void
    {
        $branch = Branch::factory()->create(['activo' => true, 'version' => 1]);

        $this->actingAsRole('recepcion');

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/desactivar", [
            'motivo' => 'Intento no permitido',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_administracion_cannot_change_status(): void
    {
        $branch = Branch::factory()->create(['activo' => true, 'version' => 1]);

        $this->actingAsRole('administracion');

        $this->postJson("/api/organizacion/sucursales/{$branch->id}/activar", [
            'motivo' => 'Intento no permitido',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_missing_branch_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales/999999/desactivar', [
            'motivo' => 'No existe',
            'version' => 1,
        ])
            ->assertNotFound()
            ->assertJsonPath('code', 'BRANCH_NOT_FOUND');
    }
}
