<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class SessionInactivityTest extends SeguridadTestCase
{
    public function test_session_expires_after_inactivity_and_token_is_deleted(): void
    {
        $user = $this->superadmin();
        $token = $user->createToken('test', ['*'], now()->addHour());
        $plain = $token->plainTextToken;

        $token->accessToken->forceFill(['last_used_at' => now()->subMinutes(20)])->save();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'SESSION_EXPIRED');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'SESION_EXPIRADA',
            'entidad' => 'sesion',
            'motivo' => 'inactividad',
            'resultado' => 'exito',
        ]);
    }

    public function test_activity_updates_last_used_at(): void
    {
        $user = $this->superadmin();
        $token = $user->createToken('test', ['*'], now()->addHour());
        $plain = $token->plainTextToken;

        $token->accessToken->forceFill(['last_used_at' => now()->subMinutes(5)])->save();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/auth/me')
            ->assertOk();

        $updated = $token->accessToken->fresh();

        $this->assertNotNull($updated->last_used_at);
        $this->assertTrue($updated->last_used_at->greaterThan(now()->subMinute()));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->superadmin();
        $token = $user->createToken('test', ['*'], now()->subMinute());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
