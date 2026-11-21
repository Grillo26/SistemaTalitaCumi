<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class SessionLimitTest extends SeguridadTestCase
{
    public function test_new_login_replaces_the_oldest_session(): void
    {
        $user = $this->superadmin();

        $first = $this->login($user->email)->assertOk();
        $firstToken = (string) $first->json('data.token');

        $second = $this->login($user->email)->assertOk();
        $secondToken = (string) $second->json('data.token');

        $this->assertNotNull(PersonalAccessToken::findToken($secondToken));
        $this->assertNull(PersonalAccessToken::findToken($firstToken));
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'INICIAR_SESION',
            'resultado' => 'exito',
        ]);
    }

    public function test_configured_limit_is_respected(): void
    {
        Parametro::query()
            ->where('clave', 'sesion_maximas_por_usuario')
            ->update(['valor' => '2', 'version' => 2]);

        Cache::flush();

        $user = $this->superadmin();

        $tokens = [];

        for ($i = 0; $i < 3; $i++) {
            $tokens[] = (string) $this->login($user->email)->assertOk()->json('data.token');
        }

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertNull(PersonalAccessToken::findToken($tokens[0]));
        $this->assertNotNull(PersonalAccessToken::findToken($tokens[1]));
        $this->assertNotNull(PersonalAccessToken::findToken($tokens[2]));
    }
}
