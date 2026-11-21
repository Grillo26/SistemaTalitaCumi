<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class LoginLockoutTest extends SeguridadTestCase
{
    public function test_account_is_locked_after_max_attempts_and_unlocks_when_expires(): void
    {
        $user = $this->superadmin();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->login($user->email, 'Incorrecta123!')->assertStatus(401);
        }

        $user->refresh();

        $this->assertSame(5, $user->intentos_fallidos);
        $this->assertNotNull($user->bloqueado_hasta);
        $this->assertTrue($user->bloqueado_hasta->isFuture());

        $this->login($user->email)->assertStatus(401);

        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'BLOQUEAR',
            'resultado' => 'denegado',
            'critico' => true,
        ]);

        $this->travel(16)->minutes();

        $this->login($user->email)->assertOk();

        $user->refresh();

        $this->assertSame(0, $user->intentos_fallidos);
        $this->assertNull($user->bloqueado_hasta);
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = $this->superadmin();

        $this->login($user->email, 'Incorrecta123!')->assertStatus(401);
        $this->login($user->email, 'Incorrecta123!')->assertStatus(401);

        $this->assertSame(2, $user->fresh()->intentos_fallidos);

        $this->login($user->email)->assertOk();

        $this->assertSame(0, $user->fresh()->intentos_fallidos);
    }

    public function test_lock_audit_is_critical_and_does_not_contain_the_password(): void
    {
        $user = $this->superadmin();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->login($user->email, 'Incorrecta123!')->assertStatus(401);
        }

        $entries = BitacoraEntry::query()->where('usuario_id', $user->id)->get();

        $this->assertTrue($entries->contains(fn (BitacoraEntry $entry): bool => $entry->accion === 'BLOQUEAR' && $entry->critico));
        $this->assertStringNotContainsString('Incorrecta123!', (string) json_encode($entries->pluck('datos_nuevos')->all()));
    }
}
