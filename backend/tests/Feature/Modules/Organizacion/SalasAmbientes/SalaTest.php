<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;

final class SalaTest extends SalasAmbientesTestCase
{
    public function test_superadmin_creates_sala_and_audits(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Sala Ositos')
            ->assertJsonPath('data.sucursal.id', $branch->id)
            ->assertJsonPath('data.tipo_sala.nombre', 'Preescolar')
            ->assertJsonPath('data.turno.nombre', 'Mañana')
            ->assertJsonPath('data.estado', 'activa')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('sala', [
            'sucursal_id' => $branch->id,
            'nombre' => 'Sala Ositos',
            'activo' => true,
        ]);

        $audit = BitacoraEntry::query()->where('entidad', 'sala')->sole();

        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame($branch->id, $audit->sucursal_id);
    }

    public function test_cannot_create_sala_in_inactive_branch(): void
    {
        $branch = $this->branch(false);
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_INACTIVE');
    }

    public function test_duplicate_sala_combination_is_rejected_with_conflict(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))
            ->assertCreated();

        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch, [
            'nombre' => 'Otra sala',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'SALA_DUPLICADA')
            ->assertJsonPath('conflict.nombre', 'Sala Ositos')
            ->assertJsonValidationErrors('tipo_sala_id');
    }

    public function test_inactive_type_turno_and_area_are_rejected(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $inactiveType = TipoSala::factory()->create(['activo' => false]);
        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch, [
            'tipo_sala_id' => $inactiveType->id,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo_sala_id')
            ->assertJsonPath('code', 'CATALOGO_INACTIVO');

        $inactiveTurno = TurnoCatalogo::factory()->create(['activo' => false]);
        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch, [
            'turno_catalogo_id' => $inactiveTurno->id,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('turno_catalogo_id');

        $inactiveArea = Area::factory()->create(['activo' => false]);
        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch, [
            'area_id' => $inactiveArea->id,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('area_id');
    }

    public function test_superadmin_lists_searches_and_filters_salas(): void
    {
        $branch = $this->branch();
        $tipo = $this->tipoSala('Preescolar');
        $turno = $this->turno('Mañana');

        Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'tipo_sala_id' => $tipo->id,
            'turno_catalogo_id' => $turno->id,
            'nombre' => 'Sala Ositos',
            'activo' => true,
        ]);
        Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'tipo_sala_id' => $this->tipoSala('Escolar')->id,
            'turno_catalogo_id' => $this->turno('Tarde')->id,
            'nombre' => 'Sala Soles',
            'activo' => false,
        ]);

        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/salas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/organizacion/salas-ambientes/salas?search=Ositos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Sala Ositos');

        $this->getJson('/api/organizacion/salas-ambientes/salas?estado=inactiva')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Sala Soles');

        $this->getJson("/api/organizacion/salas-ambientes/salas?tipo_sala_id={$tipo->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Sala Ositos');
    }

    public function test_administracion_can_list_and_edit_data_with_contract(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Sala Original',
            'observaciones' => 'Original',
            'version' => 1,
        ]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/salas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('can.salas.create', false)
            ->assertJsonPath('can.salas.update_structure', false)
            ->assertJsonPath('can.salas.update_data', true);

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'nombre' => 'Sala Actualizada',
            'observaciones' => 'Actualizada',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Sala Actualizada')
            ->assertJsonPath('data.version', 2);
    }

    public function test_administracion_cannot_edit_structural_fields(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'tipo_sala_id' => $this->tipoSala('Escolar')->id,
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_reception_can_view_but_cannot_write_salas(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);

        $user = $this->actingAsRole('recepcion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/salas')
            ->assertOk()
            ->assertJsonPath('can.salas.create', false)
            ->assertJsonPath('can.salas.update_data', false);
        $this->getJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")->assertOk();

        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))
            ->assertForbidden();
        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'nombre' => 'No permitido',
            'version' => 1,
        ])->assertForbidden();
        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/desactivar", [
            'motivo' => 'No permitido',
            'version' => 1,
        ])->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")
            ->assertForbidden();
    }

    public function test_scope_blocks_sala_outside_accessible_branches(): void
    {
        $accessible = $this->branch();
        $other = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $other->id, 'version' => 1]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$accessible->id]);

        $this->getJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'nombre' => 'Fuera de alcance',
            'version' => 1,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_non_global_user_without_assigned_branches_sees_empty_list(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/salas-ambientes/salas')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_stale_version_returns_conflict(): void
    {
        $sala = Sala::factory()->create(['version' => 5]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'nombre' => 'Otro nombre',
            'version' => 4,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SPACE_VERSION_CONFLICT');
    }

    public function test_state_change_through_update_is_prohibited(): void
    {
        $sala = Sala::factory()->create(['version' => 1]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'activo' => false,
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('activo');
    }

    public function test_update_without_fields_is_rejected(): void
    {
        $sala = Sala::factory()->create(['version' => 1]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_deactivate_and_activate_sala_with_reason_rules(): void
    {
        $sala = Sala::factory()->create(['activo' => true, 'version' => 1]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/desactivar", [
            'motivo' => 'Refacción',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'sala',
            'entidad_id' => $sala->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Refacción',
        ]);

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/activar", [
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 3);
    }

    public function test_deactivation_requires_reason_and_same_status_is_rejected(): void
    {
        $sala = Sala::factory()->create(['activo' => true, 'version' => 1]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo')
            ->assertJsonPath('code', 'STATUS_REASON_REQUIRED');

        $inactive = Sala::factory()->create(['activo' => false, 'version' => 1]);

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$inactive->id}/desactivar", [
            'motivo' => 'Ya estaba inactiva',
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'STATUS_UNCHANGED');
    }

    public function test_delete_is_always_rejected(): void
    {
        $sala = Sala::factory()->create(['nombre' => 'Sala Ositos']);
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'SPACE_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('sala', ['id' => $sala->id]);
    }

    public function test_missing_sala_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/salas/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'SALA_NOT_FOUND');
    }
}
