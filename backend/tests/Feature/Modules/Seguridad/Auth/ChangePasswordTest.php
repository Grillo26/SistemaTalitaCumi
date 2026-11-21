<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class ChangePasswordTest extends SeguridadTestCase
{
    public function test_user_can_change_password_and_other_sessions_are_revoked(): void
    {
        $user = $this->superadmin();

        $login = $this->login($user->email)->assertOk();
        $currentToken = (string) $login->json('data.token');
        $otherToken = $user->createToken('otra', ['*'], now()->addHour());
        $otherPlain = $otherToken->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->postJson('/api/auth/cambiar-password', [
                'current_password' => self::PASSWORD,
                'password' => 'NuevaClave456!',
                'password_confirmation' => 'NuevaClave456!',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Contraseña actualizada correctamente.');

        $user->refresh();

        $this->assertTrue(Hash::check('NuevaClave456!', $user->password));
        $this->assertFalse($user->debe_cambiar_password);
        $this->assertNotNull($user->password_actualizada_en);

        $this->assertNotNull(PersonalAccessToken::findToken($currentToken));
        $this->assertNull(PersonalAccessToken::findToken($otherPlain));

        $this->assertSame(1, DB::table('usuario_password_historial')->where('usuario_id', $user->id)->count());

        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $user->id,
            'accion' => 'CAMBIAR_PASSWORD',
            'entidad' => 'usuario',
            'resultado' => 'exito',
        ]);

        $this->login($user->email, 'NuevaClave456!')->assertOk();
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->superadmin();
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => 'Incorrecta123!',
            'password' => 'NuevaClave456!',
            'password_confirmation' => 'NuevaClave456!',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'CURRENT_PASSWORD_INVALID')
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_policy_is_enforced(): void
    {
        $user = $this->superadmin();
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => self::PASSWORD,
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PASSWORD_POLICY_VIOLATION')
            ->assertJsonValidationErrors('password');
    }

    public function test_current_and_previous_passwords_cannot_be_reused(): void
    {
        $user = $this->superadmin();
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => self::PASSWORD,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PASSWORD_POLICY_VIOLATION');

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => self::PASSWORD,
            'password' => 'NuevaClave456!',
            'password_confirmation' => 'NuevaClave456!',
        ])->assertOk();

        $this->postJson('/api/auth/cambiar-password', [
            'current_password' => 'NuevaClave456!',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PASSWORD_POLICY_VIOLATION');
    }
}
