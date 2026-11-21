<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserPasswordResetTest extends SeguridadTestCase
{
    public function test_reset_returns_temporary_password_once_and_invalidates_sessions(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);
        $token = $target->createToken('target', ['*'], now()->addHour());

        Sanctum::actingAs($actor);

        $response = $this->postJson("/api/seguridad/usuarios/{$target->id}/resetear-password")
            ->assertOk()
            ->assertJsonPath('sessions_invalidated', 1)
            ->assertJsonStructure(['password_temporal']);

        $temporaryPassword = (string) $response->json('password_temporal');

        $this->assertNotSame('', $temporaryPassword);

        $target->refresh();

        $this->assertTrue($target->debe_cambiar_password);
        $this->assertTrue(Hash::check($temporaryPassword, $target->password));
        $this->assertNull(PersonalAccessToken::findToken($token->plainTextToken));

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'RESETEAR_PASSWORD')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->critico);
        $this->assertStringNotContainsString($temporaryPassword, (string) json_encode($entry->datos_nuevos));

        $this->login($target->email, $temporaryPassword)
            ->assertOk()
            ->assertJsonPath('data.user.debe_cambiar_password', true);

        $this->login($target->email)->assertStatus(401);
    }

    public function test_administracion_cannot_reset_password(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $target = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($admin);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/resetear-password")
            ->assertForbidden();
    }
}
