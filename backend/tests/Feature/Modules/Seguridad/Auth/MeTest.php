<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class MeTest extends SeguridadTestCase
{
    public function test_superadmin_sees_all_active_branches_and_global_scope(): void
    {
        $central = $this->branch(['codigo' => 'SUC-001', 'nombre' => 'Central']);
        $this->branch(['codigo' => 'SUC-002', 'nombre' => 'Norte']);
        $user = $this->superadmin();
        $user->branches()->attach($central->id);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.alcance_global', true)
            ->assertJsonPath('data.requires_branch_assignment', false)
            ->assertJsonCount(2, 'data.sucursales')
            ->assertJsonPath('data.password_policy.min_length', 10)
            ->assertJsonPath('data.password_policy.history_count', 5);
    }

    public function test_administracion_sees_only_assigned_branches_and_read_only_permissions(): void
    {
        $assigned = $this->branch(['codigo' => 'SUC-001', 'nombre' => 'Central']);
        $this->branch(['codigo' => 'SUC-002', 'nombre' => 'Norte']);

        $user = $this->administracion();
        $user->branches()->attach($assigned->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.alcance_global', false)
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonPath('data.sucursales.0.codigo', 'SUC-001');

        $this->assertContains('usuario.ver', $response->json('data.permisos'));
        $this->assertNotContains('usuario.crear', $response->json('data.permisos'));
        $this->assertNotContains('rol.editar_permisos', $response->json('data.permisos'));
    }

    public function test_recepcion_without_branches_requires_assignment_and_has_no_security_permissions(): void
    {
        $user = $this->recepcion();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.requires_branch_assignment', true)
            ->assertJsonCount(0, 'data.sucursales');

        $this->assertNotContains('usuario.ver', $response->json('data.permisos'));
        $this->assertNotContains('rol.ver', $response->json('data.permisos'));
        $this->assertNotContains('bitacora.ver', $response->json('data.permisos'));
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }
}
