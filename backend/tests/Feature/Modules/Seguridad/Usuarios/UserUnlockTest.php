<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserUnlockTest extends SeguridadTestCase
{
    public function test_unlock_clears_block_and_audits_with_reason(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch, [
            'bloqueado_hasta' => now()->addMinutes(10),
            'intentos_fallidos' => 5,
        ]);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desbloquear", [
            'motivo' => 'Verificación telefónica',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.bloqueado', false)
            ->assertJsonPath('data.intentos_fallidos', 0)
            ->assertJsonPath('data.version', 2);

        $target->refresh();

        $this->assertNull($target->bloqueado_hasta);
        $this->assertSame(0, $target->intentos_fallidos);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'DESBLOQUEAR')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->critico);
        $this->assertSame('Verificación telefónica', $entry->motivo);
    }

    public function test_unlock_not_blocked_is_conflict(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desbloquear", [
            'motivo' => 'Sin bloqueo',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'USER_NOT_BLOCKED');
    }

    public function test_unlock_requires_reason(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch, [
            'bloqueado_hasta' => now()->addMinutes(10),
            'intentos_fallidos' => 5,
        ]);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desbloquear", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_administracion_cannot_unlock(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $target = $this->managedUser('recepcion', $branch, [
            'bloqueado_hasta' => now()->addMinutes(10),
            'intentos_fallidos' => 5,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/desbloquear", [
            'motivo' => 'Intento',
            'version' => 1,
        ])->assertForbidden();
    }
}
