<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class PasswordExpiryTest extends SeguridadTestCase
{
    public function test_expired_password_forces_change_on_login(): void
    {
        Parametro::query()
            ->where('clave', 'password_caducidad_dias')
            ->update(['valor' => '1', 'version' => 2]);

        Cache::flush();

        $user = $this->superadmin([
            'password_actualizada_en' => now()->subDays(2),
            'debe_cambiar_password' => false,
        ]);

        $login = $this->login($user->email)->assertOk();
        $login->assertJsonPath('data.user.debe_cambiar_password', true);

        $token = (string) $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.debe_cambiar_password', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/organizacion/configuracion/parametros')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/cambiar-password', [
                'current_password' => self::PASSWORD,
                'password' => 'NuevaClave456!',
                'password_confirmation' => 'NuevaClave456!',
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.debe_cambiar_password', false);
    }
}
