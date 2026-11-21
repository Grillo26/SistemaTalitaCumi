<?php

namespace Tests\Feature\Modules\Seguridad\Roles;

use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class RoleMatrixListTest extends SeguridadTestCase
{
    public function test_superadmin_lists_matrix_with_roles_modules_and_abilities(): void
    {
        $actor = $this->superadmin();

        Sanctum::actingAs($actor);

        $response = $this->getJson('/api/seguridad/roles')
            ->assertOk()
            ->assertJsonPath('can.edit', true)
            ->assertJsonPath('can.export', false);

        $this->assertCount(3, $response->json('data.roles'));
        $this->assertSame(
            Permission::query()->where('guard_name', 'web')->count(),
            $response->json('data.total_permisos'),
        );
        $this->assertNotEmpty($response->json('data.modulos'));

        $superadmin = collect($response->json('data.roles'))->firstWhere('nombre', 'superadmin');

        $this->assertTrue($superadmin['es_superadmin']);
        $this->assertNotEmpty($superadmin['permisos']);
        $this->assertSame('Superadmin', $superadmin['label']);
        $this->assertIsInt($superadmin['version']);

        $modules = collect($response->json('data.modulos'))->pluck('clave')->all();

        $this->assertContains('usuario', $modules);
        $this->assertContains('rol', $modules);
        $this->assertContains('bitacora', $modules);
        $this->assertContains('sucursal', $modules);
    }

    public function test_administracion_can_read_but_not_edit(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($admin);

        $this->getJson('/api/seguridad/roles')
            ->assertOk()
            ->assertJsonPath('can.edit', false)
            ->assertJsonPath('can.export', false);
    }

    public function test_recepcion_has_no_access_to_matrix(): void
    {
        $branch = $this->branch();
        $reception = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($reception);

        $this->getJson('/api/seguridad/roles')->assertForbidden();
    }

    public function test_show_returns_role_and_modules(): void
    {
        $actor = $this->superadmin();
        $roleId = $this->roleId('administracion');

        Sanctum::actingAs($actor);

        $this->getJson("/api/seguridad/roles/{$roleId}")
            ->assertOk()
            ->assertJsonPath('data.role.nombre', 'administracion')
            ->assertJsonPath('can.edit', true)
            ->assertJsonStructure([
                'data' => [
                    'role' => ['id', 'nombre', 'label', 'version', 'permisos', 'total_permisos', 'es_superadmin', 'can'],
                    'modulos' => [['clave', 'label', 'acciones']],
                ],
            ]);
    }

    public function test_show_missing_role_returns_not_found(): void
    {
        Sanctum::actingAs($this->superadmin());

        $this->getJson('/api/seguridad/roles/999999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'ROLE_NOT_FOUND');
    }
}
