<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class HttpsTest extends SeguridadTestCase
{
    public function test_http_is_rejected_when_forced_https_is_enabled(): void
    {
        config(['seguridad.forzar_https' => true]);

        $this->postJson('/api/auth/login', [
            'identificador' => 'nadie@example.test',
            'password' => 'Incorrecta123!',
        ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'HTTPS_REQUIRED');
    }

    public function test_http_is_allowed_in_development_by_default(): void
    {
        $this->postJson('/api/auth/login', [
            'identificador' => 'nadie@example.test',
            'password' => 'Incorrecta123!',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }
}
