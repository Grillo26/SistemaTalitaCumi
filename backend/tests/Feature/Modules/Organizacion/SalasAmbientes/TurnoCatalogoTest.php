<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TurnoCatalogo;

final class TurnoCatalogoTest extends SalasAmbientesTestCase
{
    public function test_superadmin_creates_turno_and_audits(): void
    {
        $user = $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', [
            'nombre' => '  Noche ',
            'orden' => 3,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Noche')
            ->assertJsonPath('data.orden', 3)
            ->assertJsonPath('data.estado', 'activo');

        $audit = BitacoraEntry::query()->where('entidad', 'turno_catalogo')->sole();

        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertNull($audit->sucursal_id);
    }

    public function test_duplicate_turno_is_rejected_case_and_space_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', [
            'nombre' => '  mañana ',
            'orden' => 9,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'TURNO_DUPLICADO');
    }

    public function test_superadmin_updates_turno_and_audits(): void
    {
        $turno = $this->turno('Mañana');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}", [
            'nombre' => 'Mañana extendida',
            'orden' => 5,
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Mañana extendida')
            ->assertJsonPath('data.orden', 5)
            ->assertJsonPath('data.version', 2);

        $audit = BitacoraEntry::query()->where('entidad', 'turno_catalogo')->where('accion', 'ACTUALIZAR')->sole();

        $this->assertSame('Mañana', $audit->datos_anteriores['nombre']);
        $this->assertSame('Mañana extendida', $audit->datos_nuevos['nombre']);
    }

    public function test_deactivating_turno_with_active_salas_is_allowed_but_blocks_new_salas(): void
    {
        $turno = $this->turno('Mañana');
        $branch = $this->branch();
        $tipo = $this->tipoSala('Preescolar');

        Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'tipo_sala_id' => $tipo->id,
            'turno_catalogo_id' => $turno->id,
            'activo' => true,
        ]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}/desactivar", [
            'motivo' => 'Turno sin uso futuro',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false);

        // Nueva sala con el mismo turno inactivo: debe rechazarse.
        $otherType = $this->tipoSala('Escolar');

        $this->postJson('/api/organizacion/salas-ambientes/salas', [
            'sucursal_id' => $branch->id,
            'area_id' => $this->area()->id,
            'tipo_sala_id' => $otherType->id,
            'turno_catalogo_id' => $turno->id,
            'nombre' => 'Sala Nocturna',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('turno_catalogo_id')
            ->assertJsonPath('code', 'CATALOGO_INACTIVO');
    }

    public function test_can_activate_turno_without_reason(): void
    {
        $turno = TurnoCatalogo::factory()->create(['activo' => false, 'version' => 2]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}/activar", [
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 3);
    }

    public function test_delete_is_always_rejected(): void
    {
        $turno = $this->turno('Tarde');
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATALOG_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('turno_catalogo', ['id' => $turno->id]);
    }

    public function test_list_orders_by_orden_and_includes_can(): void
    {
        $this->actingAsRole('recepcion');

        $response = $this->getJson('/api/organizacion/salas-ambientes/turnos-catalogo')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Mañana')
            ->assertJsonPath('data.1.nombre', 'Tarde')
            ->assertJsonPath('can.catalogos.turnos.create', false);

        $this->assertIsArray($response->json('data'));
    }

    public function test_missing_turno_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/turnos-catalogo/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'TURNO_NOT_FOUND');
    }

    public function test_reception_cannot_write_turnos(): void
    {
        $turno = $this->turno('Tarde');
        $this->actingAsRole('recepcion');

        $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', [
            'nombre' => 'Noche',
            'orden' => 9,
        ])->assertForbidden();

        $this->patchJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}", [
            'nombre' => 'Tarde extendida',
            'version' => 1,
        ])->assertForbidden();
    }
}
