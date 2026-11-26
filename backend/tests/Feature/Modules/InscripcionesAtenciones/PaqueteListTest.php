<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;

final class PaqueteListTest extends PaquetesSesionesTestCase
{
    public function test_list_is_scoped_to_user_branches(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario)->assertCreated();

        $otraSucursal = $this->branch();
        PaqueteSesiones::query()->create([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $otraSucursal->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => null,
            'tarifa_id' => $escenario['tarifa']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => [1, 3],
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-31',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
        ]);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.sucursal.id', $escenario['branch']->id);
    }

    public function test_list_shows_derived_expired_state(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?estado=vencido')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.estado', 'activo')
            ->assertJsonPath('data.0.estado_mostrado', 'vencido')
            ->assertJsonPath('data.0.estado_label', 'Vencido');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?estado=activo')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_list_filters_by_patient_search_and_period(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario)->assertCreated();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?search='
            .urlencode($escenario['paciente']->codigo))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?search=NO-EXISTE-XYZ')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?'
            .http_build_query([
                'periodo_desde' => '2026-11-01',
                'periodo_hasta' => '2026-11-30',
            ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?'
            .http_build_query([
                'periodo_desde' => '2027-01-01',
                'periodo_hasta' => '2027-01-31',
            ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_list_filters_by_service_and_professional(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario)->assertCreated();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?servicio_id='
            .$escenario['servicio']->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?profesional_id='
            .$escenario['profesional']->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_list_does_not_expose_sensitive_fields(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario, [
            'observaciones' => 'Texto administrativo interno',
        ])->assertCreated();

        $row = $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones')
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('observaciones', $row);
        $this->assertArrayNotHasKey('telefono', $row['tutor']);
        $this->assertArrayNotHasKey('alergias', $row['paciente']);
        $this->assertArrayNotHasKey('condiciones_medicas', $row['paciente']);
    }

    public function test_list_rejects_branch_filter_outside_scope(): void
    {
        $this->escenarioPaquete();
        $otraSucursal = $this->branch();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones?sucursal_id='
            .$otraSucursal->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_show_returns_ficha_with_masked_tutor_contact(): void
    {
        $escenario = $this->escenarioPaquete();
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->actingAsWithPermissions(['paquete.ver'], $escenario['branch']);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertOk()
            ->assertJsonPath('data.paciente.id', $escenario['paciente']->id)
            ->assertJsonPath('data.servicio.id', $escenario['servicio']->id)
            ->assertJsonPath('data.profesional.id', $escenario['profesional']->id)
            ->assertJsonPath('data.tutor.contacto_protegido', true)
            ->assertJsonPath('data.tutor.telefono', null);
    }

    public function test_show_returns_full_tutor_contact_with_permission(): void
    {
        $escenario = $this->escenarioPaquete('superadmin');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertOk()
            ->assertJsonPath('data.tutor.contacto_protegido', false)
            ->assertJsonPath('data.tutor.telefono', $escenario['tutor']->telefono);
    }

    public function test_show_returns_not_found(): void
    {
        $this->escenarioPaquete();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/999999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'PAQUETE_NOT_FOUND');
    }
}
