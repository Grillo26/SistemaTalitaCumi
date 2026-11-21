<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserHistoryTest extends SeguridadTestCase
{
    public function test_history_lists_create_edit_and_deactivate_events(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $created = $this->postJson('/api/seguridad/usuarios', [
            'name' => 'Historial Usuario',
            'documento' => '5555555',
            'email' => 'historial@example.test',
            'username' => 'historial.usuario',
            'rol_id' => $this->roleId('administracion'),
            'sucursales' => [$branch->id],
        ])->assertCreated();

        $userId = (int) $created->json('data.id');

        $this->patchJson("/api/seguridad/usuarios/{$userId}", [
            'name' => 'Historial Editado',
            'version' => 1,
        ])->assertOk();

        $this->postJson("/api/seguridad/usuarios/{$userId}/desactivar", [
            'motivo' => 'Fin de contrato',
            'version' => 2,
        ])->assertOk();

        $response = $this->getJson("/api/seguridad/usuarios/{$userId}/historial")
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('accion')->all();

        $this->assertContains('CREAR', $actions);
        $this->assertContains('EDITAR', $actions);
        $this->assertContains('DESACTIVAR', $actions);

        $edit = collect($response->json('data'))->firstWhere('accion', 'EDITAR');

        $this->assertArrayHasKey('datos_anteriores', $edit);
        $this->assertArrayHasKey('datos_nuevos', $edit);
        $this->assertArrayNotHasKey('password', $edit['datos_anteriores']);
        $this->assertArrayNotHasKey('password', $edit['datos_nuevos']);
    }

    public function test_history_outside_scope_is_forbidden(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $adminA = $this->managedUser('administracion', $branchA);
        $targetB = $this->managedUser('recepcion', $branchB);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/seguridad/usuarios/{$targetB->id}/historial")
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_ACCESS_DENIED');
    }
}
