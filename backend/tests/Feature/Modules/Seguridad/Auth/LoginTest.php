<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class LoginTest extends SeguridadTestCase
{
    public function test_login_success_returns_token_user_permissions_and_policy(): void
    {
        $branch = $this->branch();
        $user = $this->superadmin([
            'username' => 'super.admin',
            'documento' => '1234567',
        ]);
        $user->branches()->attach($branch->id);

        $response = $this->login($user->email);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.username', 'super.admin')
            ->assertJsonPath('data.user.alcance_global', true)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'expires_at',
                    'user' => [
                        'id',
                        'nombre_completo',
                        'rol',
                        'permisos',
                        'sucursales',
                        'debe_cambiar_password',
                        'password_policy' => [
                            'min_length',
                            'require_uppercase',
                            'require_lowercase',
                            'require_number',
                            'require_symbol',
                            'history_count',
                            'expiry_days',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame(0, $user->fresh()->intentos_fallidos);
        $this->assertNotNull($user->fresh()->ultimo_acceso);
    }

    public function test_login_accepts_username_case_insensitively(): void
    {
        $user = $this->superadmin(['username' => 'Super.Admin']);

        $this->login('super.admin')
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_wrong_password_and_unknown_user_return_the_same_generic_message(): void
    {
        $user = $this->superadmin();

        $wrong = $this->login($user->email, 'Incorrecta123!');
        $unknown = $this->login('no-existe@example.test', 'Incorrecta123!');

        $wrong->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('message', 'Usuario o contraseña incorrectos.');

        $unknown->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('message', 'Usuario o contraseña incorrectos.');

        $this->assertSame(
            $wrong->json('message'),
            $unknown->json('message'),
        );
    }

    public function test_inactive_user_gets_generic_error_and_denied_audit(): void
    {
        $user = $this->superadmin(['activo' => false]);

        $this->login($user->email)
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $entry = BitacoraEntry::query()
            ->where('accion', 'LOGIN_FALLIDO')
            ->where('usuario_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('denegado', $entry->resultado);
        $this->assertSame('cuenta_inactiva', $entry->motivo);
        $this->assertSame('autenticacion', $entry->modulo);
    }

    public function test_blocked_user_gets_generic_error_even_with_correct_password(): void
    {
        $user = $this->superadmin([
            'bloqueado_hasta' => now()->addMinutes(10),
            'intentos_fallidos' => 5,
        ]);

        $this->login($user->email)
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $entry = BitacoraEntry::query()
            ->where('accion', 'LOGIN_FALLIDO')
            ->where('usuario_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('cuenta_bloqueada', $entry->motivo);
        $this->assertSame('denegado', $entry->resultado);
    }

    public function test_audit_never_contains_password_hash_or_token(): void
    {
        $user = $this->superadmin(['password' => Hash::make('OtraClave123!')]);

        $success = $this->login($user->email, 'OtraClave123!');
        $success->assertOk();

        $token = (string) $success->json('data.token');

        $this->login($user->email, 'Incorrecta123!')->assertStatus(401);

        $entries = BitacoraEntry::query()->get();

        foreach ($entries as $entry) {
            $json = json_encode($entry->datos_anteriores).json_encode($entry->datos_nuevos);

            $this->assertStringNotContainsString('OtraClave123!', $json);
            $this->assertStringNotContainsString('Incorrecta123!', $json);
            $this->assertStringNotContainsString($token, $json);
            $this->assertStringNotContainsString((string) $user->password, $json);
        }
    }

    public function test_last_access_is_exposed_after_login(): void
    {
        $user = $this->superadmin();

        $this->login($user->email)->assertOk();

        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure(['data' => ['ultimo_acceso']]);
    }
}
