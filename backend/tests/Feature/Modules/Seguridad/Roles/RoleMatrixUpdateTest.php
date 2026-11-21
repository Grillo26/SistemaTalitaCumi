<?php

namespace Tests\Feature\Modules\Seguridad\Roles;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class RoleMatrixUpdateTest extends SeguridadTestCase
{
    public function test_superadmin_updates_role_permissions_and_audits(): void
    {
        $actor = $this->superadmin();
        $role = Role::findByName('administracion', 'web');
        $current = $this->rolePermissionIds('administracion');
        $remove = $this->permissionId('usuario.exportar');
        $new = array_values(array_diff($current, [$remove]));
        $version = $this->roleVersion('administracion');

        Sanctum::actingAs($actor);

        $response = $this->putJson("/api/seguridad/roles/{$role->id}/permisos", [
            'permisos' => $new,
            'version' => $version,
        ])
            ->assertOk()
            ->assertJsonPath('data.role.version', $version + 1);

        $this->assertNotContains($remove, $response->json('data.role.permisos'));
        $this->assertSame($version + 1, Role::findByName('administracion', 'web')->version);
        $this->assertFalse(Role::findByName('administracion', 'web')->hasPermissionTo('usuario.exportar'));

        $entry = BitacoraEntry::query()
            ->where('entidad', 'matriz_permisos')
            ->where('entidad_id', $role->id)
            ->where('accion', 'EDITAR_PERMISOS')
            ->where('critico', true)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($actor->id, $entry->usuario_id);
        $this->assertContains('usuario.exportar', $entry->datos_anteriores['permisos']);
        $this->assertNotContains('usuario.exportar', $entry->datos_nuevos['permisos']);
        $this->assertSame($version + 1, $entry->datos_nuevos['version']);
    }

    public function test_permission_change_applies_on_next_request(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $superadmin = $this->superadmin();
        $roleId = $this->roleId('administracion');
        $current = $this->rolePermissionIds('administracion');
        $remove = $this->permissionId('usuario.ver');
        $version = $this->roleVersion('administracion');

        Sanctum::actingAs($admin);
        $this->getJson('/api/seguridad/usuarios')->assertOk();

        Sanctum::actingAs($superadmin);
        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => array_values(array_diff($current, [$remove])),
            'version' => $version,
        ])->assertOk();

        Sanctum::actingAs($admin->fresh());

        $this->getJson('/api/seguridad/usuarios')->assertForbidden();
    }

    public function test_superadmin_cannot_lose_critical_permission(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('superadmin');
        $current = $this->rolePermissionIds('superadmin');
        $remove = $this->permissionId('usuario.ver');
        $version = $this->roleVersion('superadmin');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => array_values(array_diff($current, [$remove])),
            'version' => $version,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CRITICAL_PERMISSION_PROTECTED');

        $this->assertContains($remove, $this->rolePermissionIds('superadmin'));
        $this->assertSame($version, $this->roleVersion('superadmin'));
    }

    public function test_superadmin_can_lose_non_critical_permission(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('superadmin');
        $current = $this->rolePermissionIds('superadmin');
        $remove = $this->permissionId('usuario.exportar');
        $version = $this->roleVersion('superadmin');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => array_values(array_diff($current, [$remove])),
            'version' => $version,
        ])
            ->assertOk()
            ->assertJsonPath('data.role.version', $version + 1);

        $this->assertNotContains($remove, $this->rolePermissionIds('superadmin'));
    }

    public function test_unchanged_matrix_returns_conflict(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => $this->rolePermissionIds('administracion'),
            'version' => $this->roleVersion('administracion'),
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'MATRIX_UNCHANGED');
    }

    public function test_stale_version_returns_conflict(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => $this->rolePermissionIds('administracion'),
            'version' => 99,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'MATRIX_VERSION_CONFLICT');
    }

    public function test_invalid_permission_is_rejected(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => [999999],
            'version' => $this->roleVersion('administracion'),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('permisos.0');
    }

    public function test_empty_permissions_for_non_superadmin_are_allowed(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('recepcion');

        Sanctum::actingAs($actor);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => [],
            'version' => $this->roleVersion('recepcion'),
        ])
            ->assertOk()
            ->assertJsonPath('data.role.total_permisos', 0);
    }

    public function test_delete_role_is_rejected(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->deleteJson("/api/seguridad/roles/{$roleId}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'ROLE_DELETE_NOT_ALLOWED');
    }

    public function test_administracion_and_recepcion_cannot_edit_matrix(): void
    {
        $branch = $this->branch();
        $roleId = $this->roleId('recepcion');
        $version = $this->roleVersion('recepcion');

        $admin = $this->managedUser('administracion', $branch);
        Sanctum::actingAs($admin);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => [],
            'version' => $version,
        ])->assertForbidden();

        $reception = $this->managedUser('recepcion', $branch);
        Sanctum::actingAs($reception);

        $this->putJson("/api/seguridad/roles/{$roleId}/permisos", [
            'permisos' => [],
            'version' => $version,
        ])->assertForbidden();
    }
}
