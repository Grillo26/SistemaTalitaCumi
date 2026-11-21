<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AuthAuditTest extends SeguridadTestCase
{
    public function test_login_events_include_ip_module_result_and_no_secrets(): void
    {
        $user = $this->superadmin();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/auth/login', [
                'identificador' => $user->email,
                'password' => self::PASSWORD,
            ])
            ->assertOk();

        $success = BitacoraEntry::query()
            ->where('accion', 'INICIAR_SESION')
            ->where('usuario_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($success);
        $this->assertSame('203.0.113.10', $success->ip);
        $this->assertSame('autenticacion', $success->modulo);
        $this->assertSame('exito', $success->resultado);
        $this->assertSame('sesion', $success->entidad);
        $this->assertNotNull($success->entidad_id);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/auth/login', [
                'identificador' => $user->email,
                'password' => 'Incorrecta123!',
            ])
            ->assertStatus(401);

        $failure = BitacoraEntry::query()
            ->where('accion', 'LOGIN_FALLIDO')
            ->where('usuario_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($failure);
        $this->assertSame('203.0.113.10', $failure->ip);
        $this->assertSame('fallo', $failure->resultado);
        $this->assertSame(['identificador' => $user->email], $failure->datos_nuevos);
    }

    public function test_unknown_user_failure_has_null_user_and_identifier_only(): void
    {
        $this->postJson('/api/auth/login', [
            'identificador' => 'desconocido@example.test',
            'password' => 'Incorrecta123!',
        ])->assertStatus(401);

        $failure = BitacoraEntry::query()
            ->where('accion', 'LOGIN_FALLIDO')
            ->whereNull('usuario_id')
            ->latest('id')
            ->first();

        $this->assertNotNull($failure);
        $this->assertSame(['identificador' => 'desconocido@example.test'], $failure->datos_nuevos);
        $this->assertSame('credenciales_invalidas', $failure->motivo);
    }
}
