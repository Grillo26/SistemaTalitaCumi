<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;

final class TipoSalaTest extends SalasAmbientesTestCase
{
    public function test_superadmin_creates_tipo_sala_and_audits(): void
    {
        $user = $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', [
            'nombre' => '  Sala   Maternal ',
            'activo' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Sala Maternal')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('can.catalogos.tipos_sala.create', true);

        $this->assertDatabaseHas('tipo_sala', ['nombre' => 'Sala Maternal']);

        $audit = BitacoraEntry::query()->where('entidad', 'tipo_sala')->sole();

        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertNull($audit->sucursal_id);
        $this->assertSame('Sala Maternal', $audit->datos_nuevos['nombre']);
    }

    public function test_duplicate_tipo_sala_is_rejected_case_and_space_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', [
            'nombre' => '  preescolar ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'TIPO_SALA_DUPLICADO');
    }

    public function test_reception_and_administracion_cannot_create_tipo_sala(): void
    {
        $this->actingAsRole('recepcion');
        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', ['nombre' => 'Nuevo'])
            ->assertForbidden();

        $this->actingAsRole('administracion');
        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', ['nombre' => 'Nuevo 2'])
            ->assertForbidden();
    }

    public function test_superadmin_updates_tipo_sala_and_audits(): void
    {
        $tipo = $this->tipoSala('Preescolar');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}", [
            'nombre' => 'Preescolar Renovado',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Preescolar Renovado')
            ->assertJsonPath('data.version', 2);

        $audit = BitacoraEntry::query()->where('entidad', 'tipo_sala')->where('accion', 'ACTUALIZAR')->sole();

        $this->assertSame('Preescolar', $audit->datos_anteriores['nombre']);
        $this->assertSame('Preescolar Renovado', $audit->datos_nuevos['nombre']);
    }

    public function test_stale_version_returns_conflict(): void
    {
        $tipo = $this->tipoSala('Preescolar');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}", [
            'nombre' => 'Otro nombre',
            'version' => 99,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SPACE_VERSION_CONFLICT');
    }

    public function test_active_field_is_prohibited_in_update(): void
    {
        $tipo = $this->tipoSala('Preescolar');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}", [
            'activo' => false,
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('activo');
    }

    public function test_cannot_deactivate_tipo_sala_with_active_salas(): void
    {
        $tipo = $this->tipoSala('Preescolar');
        Sala::factory()->create(['tipo_sala_id' => $tipo->id, 'activo' => true]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}/desactivar", [
            'motivo' => 'Cierre del tipo',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TIPO_SALA_IN_USE')
            ->assertJsonPath('active_salas_count', 1);
    }

    public function test_can_deactivate_and_activate_tipo_sala_without_active_salas(): void
    {
        $tipo = TipoSala::factory()->create(['nombre' => 'Tipo Temporal', 'activo' => true, 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}/desactivar", [
            'motivo' => 'Cierre temporal',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tipo_sala',
            'entidad_id' => $tipo->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Cierre temporal',
        ]);

        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}/activar", [
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 3);
    }

    public function test_deactivation_requires_reason(): void
    {
        $tipo = TipoSala::factory()->create(['activo' => true, 'version' => 1]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo')
            ->assertJsonPath('code', 'STATUS_REASON_REQUIRED');
    }

    public function test_delete_is_always_rejected(): void
    {
        $tipo = $this->tipoSala('Preescolar');

        $this->actingAsSuperadmin();

        $this->deleteJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATALOG_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('tipo_sala', ['id' => $tipo->id]);
    }

    public function test_missing_tipo_sala_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/tipos-sala/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'TIPO_SALA_NOT_FOUND');

        $this->deleteJson('/api/organizacion/salas-ambientes/tipos-sala/999999')
            ->assertNotFound();
    }

    public function test_list_returns_catalog_and_can_flags(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/salas-ambientes/tipos-sala')
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'Preescolar'])
            ->assertJsonPath('can.catalogos.view', true)
            ->assertJsonPath('can.catalogos.tipos_sala.create', false);
    }

    public function test_reception_can_view_but_not_write(): void
    {
        $tipo = $this->tipoSala('Preescolar');
        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/salas-ambientes/tipos-sala')->assertOk();
        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', ['nombre' => 'Nuevo'])
            ->assertForbidden();
        $this->patchJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}", [
            'nombre' => 'Otro',
            'version' => 1,
        ])->assertForbidden();
        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}/desactivar", [
            'motivo' => 'Prohibido',
            'version' => 1,
        ])->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}")
            ->assertForbidden();
    }
}
