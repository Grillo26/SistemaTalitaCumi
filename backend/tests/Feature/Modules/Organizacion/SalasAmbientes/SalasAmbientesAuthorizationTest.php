<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\Sala;

/**
 * Matriz de permisos del submódulo (RF-017 / CA-009).
 */
final class SalasAmbientesAuthorizationTest extends SalasAmbientesTestCase
{
    public function test_reception_is_read_only_across_all_entities(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);
        $tipo = $this->tipoSala('Preescolar');
        $turno = $this->turno('Mañana');

        $user = $this->actingAsRole('recepcion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/tipos-sala')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/turnos-catalogo')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/areas')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/salas')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/ambientes')->assertOk();

        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', ['nombre' => 'Nuevo'])->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', ['nombre' => 'Nuevo', 'orden' => 9])->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))->assertForbidden();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", ['nombre' => 'X', 'version' => 1])->assertForbidden();
        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", ['nombre' => 'X', 'version' => 1])->assertForbidden();
        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/desactivar", ['motivo' => 'X', 'version' => 1])->assertForbidden();
        $this->postJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}/desactivar", ['motivo' => 'X', 'version' => 1])->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipo->id}")->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turno->id}")->assertForbidden();
    }

    public function test_administracion_can_edit_space_data_but_not_catalogs_structure_or_status(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'version' => 1]);

        $user = $this->actingAsRole('administracion');
        $this->fakeAccessFor($user, [$branch->id]);

        $this->getJson('/api/organizacion/salas-ambientes/tipos-sala')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/turnos-catalogo')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/areas')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/salas')->assertOk();
        $this->getJson('/api/organizacion/salas-ambientes/ambientes')->assertOk();

        $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', ['nombre' => 'Nuevo'])->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', ['nombre' => 'Nuevo', 'orden' => 9])->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch))->assertForbidden();
        $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch))->assertForbidden();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'nombre' => 'Sala editada',
            'version' => 1,
        ])->assertOk();

        $this->patchJson("/api/organizacion/salas-ambientes/salas/{$sala->id}", [
            'tipo_sala_id' => $this->tipoSala('Escolar')->id,
            'version' => 2,
        ])->assertForbidden();

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'nombre' => 'Ambiente editado',
            'version' => 1,
        ])->assertOk();

        $this->patchJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}", [
            'uso' => 'mixto',
            'version' => 2,
        ])->assertForbidden();

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$sala->id}/desactivar", [
            'motivo' => 'Prohibido',
            'version' => 2,
        ])->assertForbidden();

        $this->deleteJson("/api/organizacion/salas-ambientes/salas/{$sala->id}")->assertForbidden();
        $this->deleteJson("/api/organizacion/salas-ambientes/ambientes/{$ambiente->id}")->assertForbidden();
    }
}
