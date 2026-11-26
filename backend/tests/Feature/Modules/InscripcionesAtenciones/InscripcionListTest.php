<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

final class InscripcionListTest extends InscripcionesAtencionesTestCase
{
    public function test_list_is_scoped_to_user_branches(): void
    {
        $scenario = $this->scenario();
        $this->crearInscripcion($scenario)->assertCreated();

        $otherBranch = $this->branch();
        $otherSala = $this->sala($otherBranch);
        InscripcionSala::query()->create([
            'paciente_id' => $scenario['paciente']->id,
            'tutor_responsable_id' => $scenario['tutor']->id,
            'sala_id' => $otherSala->id,
            'sucursal_id' => $otherBranch->id,
            'servicio_id' => $scenario['servicio']->id,
            'tarifa_id' => $scenario['tarifa']->id,
            'categoria_tarifa_id' => $scenario['categoria']->id,
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'monto_esperado' => '500.00',
            'estado' => 'activa',
        ]);

        $response = $this->getJson('/api/inscripciones-atenciones/salas-grupales')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $response->assertJsonPath('data.0.sucursal.id', $scenario['branch']->id);
    }

    public function test_list_filters_by_period_and_derived_vencida_state(): void
    {
        $scenario = $this->scenario();
        $this->crearInscripcion($scenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated();

        $this->getJson('/api/inscripciones-atenciones/salas-grupales?periodo_mes=10&periodo_anio=2026')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.estado_mostrado', 'vencida')
            ->assertJsonPath('data.0.estado_label', 'Vencida');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales?estado=vencida')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/inscripciones-atenciones/salas-grupales?estado=activa')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_list_does_not_expose_sensitive_or_contact_fields(): void
    {
        $scenario = $this->scenario();
        $this->crearInscripcion($scenario, [
            'observaciones' => 'Texto administrativo',
        ])->assertCreated();

        $row = $this->getJson('/api/inscripciones-atenciones/salas-grupales')
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('observaciones', $row);
        $this->assertArrayNotHasKey('telefono', $row['tutor']);
        $this->assertArrayNotHasKey('alergias', $row['paciente']);
        $this->assertArrayNotHasKey('condiciones_medicas', $row['paciente']);
    }

    public function test_list_rejects_branch_filter_outside_scope(): void
    {
        $scenario = $this->scenario();
        $other = $this->branch();

        $this->getJson('/api/inscripciones-atenciones/salas-grupales?sucursal_id='.$other->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_show_returns_ficha_with_masked_tutor_contact(): void
    {
        $scenario = $this->scenario();
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->actingAsWithPermissions(['inscripcion.ver'], $scenario['branch']);

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.paciente.id', $scenario['paciente']->id)
            ->assertJsonPath('data.sala.id', $scenario['sala']->id)
            ->assertJsonPath('data.tutor.contacto_protegido', true)
            ->assertJsonPath('data.tutor.telefono', null);
    }

    public function test_show_returns_full_tutor_contact_with_permission(): void
    {
        $scenario = $this->scenario('superadmin');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.tutor.contacto_protegido', false)
            ->assertJsonPath('data.tutor.telefono', $scenario['tutor']->telefono);
    }
}
