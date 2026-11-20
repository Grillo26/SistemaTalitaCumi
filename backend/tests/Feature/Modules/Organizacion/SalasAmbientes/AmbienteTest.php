<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\BitacoraEntry;

final class AmbienteTest extends SalasAmbientesTestCase
{
    public function test_superadmin_creates_ambiente_and_audits(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Consultorio 1')
            ->assertJsonPath('data.uso', 'terapia_individual')
            ->assertJsonPath('data.uso_label', 'Terapia individual')
            ->assertJsonPath('data.sucursal.id', $branch->id)
            ->assertJsonPath('data.estado', 'activa');

        $audit = BitacoraEntry::query()->where('entidad', 'ambiente')->sole();

        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame($branch->id, $audit->sucursal_id);
    }

    public function test_duplicate_ambiente_name_is_rejected_in_same_branch(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))
            ->assertCreated();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch, [
            'nombre' => '  consultorio   1 ',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'AMBIENTE_DUPLICADO');
    }

    public function test_same_ambiente_name_is_allowed_in_another_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branchA))
            ->assertCreated();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branchB))
            ->assertCreated();
    }

    public function test_cannot_create_ambiente_in_inactive_branch(): void
    {
        $branch = $this->branch(false);
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))
            ->assertStatus(409)
            ->assertJsonPath('code', 'BRANCH_INACTIVE');
    }

    public function test_invalid_uso_is_rejected(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch, [
            'uso' => 'otro',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('uso');
    }

    public function test_superadmin_lists_and_filters_ambientes(): void
    {
        $branch = $this->branch();
        Ambiente::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Consultorio 1',
            'uso' => 'terapia_individual',
            'activo' => true,
        ]);
        Ambiente::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Entrevistas',
            'uso' => 'entrevista',
            'activo' => false,
        ]);

        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/ambientes')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/organizacion/salas-ambientes/ambientes?search=Consultorio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Consultorio 1');

        $this->getJson('/api/organizacion/salas-ambientes/ambientes?uso=entrevista')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Entrevistas');

        $this->getJson('/api/organizacion/salas-ambientes/ambientes?estado=inactiva')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_administracion_can_edit_data_with_contract_but_not_structure(): void
    {
        $branch = $this->branch();
        $ambiente = Ambiente::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Consultorio Original',
            'version' => 1,
        ]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/ambientes')
            ->assertOk()
            ->assertJsonPath('can.ambientes.update_data', true)
            ->assertJsonPath('can.ambientes.update_structure', false);

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'nombre' => 'Consultorio Actualizado',
            'observaciones' => 'Actualizado',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Consultorio Actualizado')
            ->assertJsonPath('data.version', 2);

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'uso' => 'mixto',
            'version' => 2,
        ])->assertForbidden();
    }

    public function test_reception_can_view_but_not_write_ambientes(): void
    {
        $branch = $this->branch();
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);

        $user = $this->actingAsRole('recepcion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/ambientes')
            ->assertOk()
            ->assertJsonPath('can.ambientes.create', false)
            ->assertJsonPath('can.ambientes.update_data', false);
        $this->getJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")->assertOk();

        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))
            ->assertForbidden();
        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'nombre' => 'No permitido',
            'version' => 1,
        ])->assertForbidden();
        $this->postJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}/desactivar", [
            'motivo' => 'No permitido',
            'version' => 1,
        ])->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")
            ->assertForbidden();
    }

    public function test_scope_blocks_ambiente_outside_accessible_branches(): void
    {
        $accessible = $this->branch();
        $other = $this->branch();
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $other->id, 'version' => 1]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$accessible->id]);

        $this->getJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'nombre' => 'Fuera de alcance',
            'version' => 1,
        ])
            ->assertForbidden();
    }

    public function test_non_global_user_without_contract_gets_pending_module_error(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/salas-ambientes/ambientes')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULE_02_PENDING');
    }

    public function test_stale_version_and_prohibited_state_are_rejected(): void
    {
        $ambiente = Ambiente::factory()->create(['version' => 5]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'nombre' => 'Otro',
            'version' => 4,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SPACE_VERSION_CONFLICT');

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'activo' => false,
            'version' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('activo');
    }

    public function test_deactivate_and_activate_ambiente_with_reason_rules(): void
    {
        $ambiente = Ambiente::factory()->create(['activo' => true, 'version' => 1]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}/desactivar", [
            'motivo' => 'Mantenimiento',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'ambiente',
            'entidad_id' => $ambiente->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Mantenimiento',
        ]);

        $this->postJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}/activar", [
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 3);
    }

    public function test_delete_is_always_rejected(): void
    {
        $ambiente = Ambiente::factory()->create(['nombre' => 'Consultorio 1']);
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'SPACE_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('ambiente', ['id' => $ambiente->id]);
    }

    public function test_missing_ambiente_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/salas-ambientes/ambientes/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'AMBIENTE_NOT_FOUND');
    }
}
