<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserStatusTest extends SeguridadTestCase
{
    public function test_deactivate_requires_reason_and_invalidates_sessions(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);
        $token = $target->createToken('target', ['*'], now()->addHour());

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desactivar", [
            'motivo' => 'Retiro del personal',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.estado', 'inactivo')
            ->assertJsonPath('data.motivo_estado', 'Retiro del personal')
            ->assertJsonPath('data.version', 2);

        $target->refresh();

        $this->assertFalse($target->activo);
        $this->assertSame('Retiro del personal', $target->motivo_estado);
        $this->assertNull(PersonalAccessToken::findToken($token->plainTextToken));

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'DESACTIVAR')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->critico);
        $this->assertSame('Retiro del personal', $entry->motivo);
        $this->assertSame(1, $entry->datos_nuevos['sesiones_invalidadas']);

        $this->login($target->email)->assertStatus(401);
    }

    public function test_deactivate_without_reason_is_rejected(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'USER_STATUS_REASON_REQUIRED')
            ->assertJsonValidationErrors('motivo');
    }

    public function test_activate_clears_reason_and_requires_active_branches(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch, ['activo' => false, 'motivo_estado' => 'Pausa']);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/activar", [
            'motivo' => 'Reincorporación',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.motivo_estado', null)
            ->assertJsonPath('data.version', 2);

        $target->branches()->detach();
        User::query()->whereKey($target->id)->update([
            'activo' => false,
            'version' => 3,
        ]);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/activar", [
            'version' => 3,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'USER_BRANCH_REQUIRED')
            ->assertJsonValidationErrors('sucursales');
    }

    public function test_status_unchanged_is_rejected(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/activar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'USER_STATUS_UNCHANGED');
    }

    public function test_last_superadmin_cannot_be_deactivated(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$actor->id}/desactivar", [
            'motivo' => 'Baja del último Superadmin',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'LAST_SUPERADMIN_PROTECTED');

        $this->assertTrue($actor->fresh()->activo);
    }

    public function test_last_superadmin_cannot_lose_role(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $this->patchJson("/api/seguridad/usuarios/{$actor->id}", [
            'rol_id' => $this->roleId('administracion'),
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'LAST_SUPERADMIN_PROTECTED');

        $this->assertTrue($actor->fresh()->hasRole('superadmin'));
    }

    public function test_second_superadmin_can_be_deactivated(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);
        $second = $this->managedUser('superadmin', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$second->id}/desactivar", [
            'motivo' => 'Cambio de responsable',
            'version' => 1,
        ])->assertOk();

        $this->assertFalse($second->fresh()->activo);
        $this->assertTrue($actor->fresh()->activo);
    }
}
