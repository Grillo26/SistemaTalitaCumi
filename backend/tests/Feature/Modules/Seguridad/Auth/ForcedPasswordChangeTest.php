<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class ForcedPasswordChangeTest extends SeguridadTestCase
{
    public function test_temporary_password_blocks_other_endpoints_until_changed(): void
    {
        $branch = $this->branch();
        $user = $this->recepcion(['debe_cambiar_password' => true]);
        $user->branches()->attach($branch->id);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')->assertOk();

        $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => self::PASSWORD,
            'password' => 'NuevaClave456!',
            'password_confirmation' => 'NuevaClave456!',
        ])->assertOk();

        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/organizacion/configuracion/parametros')->assertOk();
    }
}
