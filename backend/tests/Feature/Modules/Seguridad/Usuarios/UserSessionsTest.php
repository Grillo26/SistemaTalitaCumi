<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserSessionsTest extends SeguridadTestCase
{
    public function test_close_sessions_deletes_all_tokens_and_audits(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        $target->createToken('uno', ['*'], now()->addHour());
        $target->createToken('dos', ['*'], now()->addHour());

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/cerrar-sesiones", [
            'motivo' => 'Sospecha de equipo compartido',
        ])
            ->assertOk()
            ->assertJsonPath('sesiones_cerradas', 2)
            ->assertJsonPath('data.sesiones_activas', 0);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'CERRAR_SESIONES')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->critico);
        $this->assertSame('Sospecha de equipo compartido', $entry->motivo);
        $this->assertSame(2, $entry->datos_nuevos['sesiones_cerradas']);
    }

    public function test_close_sessions_requires_reason(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/cerrar-sesiones", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_close_sessions_without_tokens_is_idempotent(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/cerrar-sesiones", [
            'motivo' => 'Sin sesiones',
        ])
            ->assertOk()
            ->assertJsonPath('sesiones_cerradas', 0);
    }

    public function test_administracion_cannot_close_sessions(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $target = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($admin);

        $this->postJson("/api/seguridad/usuarios/{$target->id}/cerrar-sesiones", [
            'motivo' => 'Intento',
        ])->assertForbidden();
    }
}
