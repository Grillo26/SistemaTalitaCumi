<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Laravel\Sanctum\PersonalAccessToken;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class LogoutTest extends SeguridadTestCase
{
    public function test_logout_deletes_current_token_and_audits(): void
    {
        $user = $this->superadmin();

        $login = $this->login($user->email)->assertOk();
        $token = (string) $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'CERRAR_SESION',
            'entidad' => 'sesion',
            'resultado' => 'exito',
        ]);

        // En tests el guard conserva el usuario entre requests; en producción
        // cada request se resuelve de nuevo. Forzamos la re-resolución.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
