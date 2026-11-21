<?php

namespace Tests\Feature\Modules\Seguridad\Roles;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Database\Seeders\SeguridadMatrixBaselineSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class RoleMatrixHistoryTest extends SeguridadTestCase
{
    public function test_history_returns_baseline_and_updated_matrix(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');
        $today = now()->format('Y-m-d');

        Sanctum::actingAs($actor);

        $baseline = $this->getJson("/api/seguridad/roles/{$roleId}/historial?fecha={$today}")
            ->assertOk()
            ->assertJsonPath('data.encontrado', true);

        $this->assertContains($this->permissionId('usuario.ver'), $baseline->json('data.permisos') ? array_map(
            fn (string $name): int => $this->permissionId($name),
            $baseline->json('data.permisos'),
        ) : []);

        $current = $this->rolePermissionIds('administracion');
        $remove = $this->permissionId('usuario.exportar');
        $version = $this->roleVersion('administracion');

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => array_values(array_diff($current, [$remove])),
            'version' => $version,
        ])->assertOk();

        $updated = $this->getJson("/api/seguridad/roles/{$roleId}/historial?fecha={$today}")
            ->assertOk()
            ->assertJsonPath('data.encontrado', true);

        $this->assertSame($version + 1, $updated->json('data.version'));
        $this->assertNotContains('usuario.exportar', $updated->json('data.permisos'));
    }

    public function test_history_before_baseline_is_not_found(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->getJson("/api/seguridad/roles/{$roleId}/historial?fecha=2000-01-01")
            ->assertOk()
            ->assertJsonPath('data.encontrado', false)
            ->assertJsonPath('data.permisos', []);
    }

    public function test_history_rejects_invalid_date_and_missing_role(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->getJson("/api/seguridad/roles/{$roleId}/historial?fecha=20-01-2026")
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha');

        $this->getJson('/api/seguridad/roles/999999/historial?fecha=2026-01-20')
            ->assertStatus(404)
            ->assertJsonPath('code', 'ROLE_NOT_FOUND');
    }

    public function test_baseline_events_are_created_once(): void
    {
        $this->assertSame(3, BitacoraEntry::query()
            ->where('entidad', 'matriz_permisos')
            ->count());

        $this->seed(SeguridadMatrixBaselineSeeder::class);

        $this->assertSame(3, BitacoraEntry::query()
            ->where('entidad', 'matriz_permisos')
            ->count());

        $superadmin = Role::findByName('superadmin', 'web');
        $entry = BitacoraEntry::query()
            ->where('entidad', 'matriz_permisos')
            ->where('entidad_id', $superadmin->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue((bool) $entry->datos_nuevos['linea_base']);
        $this->assertFalse($entry->critico);
        $this->assertSame('Línea base de instalación', $entry->motivo);
    }
}
