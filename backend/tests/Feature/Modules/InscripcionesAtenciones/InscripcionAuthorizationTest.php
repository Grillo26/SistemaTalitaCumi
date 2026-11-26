<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

final class InscripcionAuthorizationTest extends InscripcionesAtencionesTestCase
{
    public function test_reception_cannot_edit_or_withdraw(): void
    {
        $scenario = $this->scenario('recepcion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'fecha_fin' => '2026-11-15',
        ])->assertForbidden();

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertForbidden();
    }

    public function test_reception_can_create_and_renew(): void
    {
        $scenario = $this->scenario('recepcion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/renovar', [])
            ->assertCreated();
    }

    public function test_show_outside_branch_scope_is_denied(): void
    {
        $otherBranch = $this->branch();
        $otherScenario = $this->scenario('superadmin');
        // The superadmin scenario creates its own branch; create a second
        // inscription directly in a branch the reception user cannot access.
        $sala = $this->sala($otherBranch);
        $inscripcion = InscripcionSala::query()->create([
            'paciente_id' => $otherScenario['paciente']->id,
            'tutor_responsable_id' => $otherScenario['tutor']->id,
            'sala_id' => $sala->id,
            'sucursal_id' => $otherBranch->id,
            'servicio_id' => $otherScenario['servicio']->id,
            'tarifa_id' => $otherScenario['tarifa']->id,
            'categoria_tarifa_id' => $otherScenario['categoria']->id,
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'monto_esperado' => '500.00',
            'estado' => 'activa',
        ]);

        $restricted = $this->actingAsWithPermissions(['inscripcion.ver'], $otherScenario['branch']);

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$inscripcion->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');

        $this->assertNotNull($restricted);
    }
}
