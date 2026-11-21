<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class SecurityConfigurationTest extends SeguridadTestCase
{
    public function test_missing_security_parameter_fails_closed(): void
    {
        $user = $this->superadmin();

        Parametro::query()->where('clave', 'password_longitud_minima')->delete();
        Cache::flush();

        $this->login($user->email)
            ->assertStatus(503)
            ->assertJsonPath('code', 'SECURITY_CONFIGURATION_MISSING')
            ->assertJsonPath('message', 'La configuración de seguridad no está disponible. Contactá al administrador.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
