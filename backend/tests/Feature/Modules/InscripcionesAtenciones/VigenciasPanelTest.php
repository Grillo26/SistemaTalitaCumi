<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class VigenciasPanelTest extends VigenciasTestCase
{
    public function test_panel_lists_por_vencer_and_vencidos_with_derived_state(): void
    {
        $escenario = $this->scenario();
        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated();

        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated();

        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-31',
        ])->assertCreated();

        $response = $this->getJson('/api/inscripciones-atenciones/vigencias/panel')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.tipo_entidad', 'inscripcion_sala');

        $estados = collect($response->json('data'))->pluck('estado_vigencia')->sort()->values()->all();

        $this->assertSame(['por_vencer', 'vencido'], $estados);
    }

    public function test_panel_can_filter_vigentes_and_derives_renovada_from_successor(): void
    {
        $escenario = $this->scenario('administracion');

        $origen = $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales/'.$origen['id'].'/renovar',
            [],
        )->assertCreated();

        $this->getJson(
            '/api/inscripciones-atenciones/vigencias/panel?estado=renovado',
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $origen['id'])
            ->assertJsonPath('data.0.estado_vigencia', 'renovado')
            ->assertJsonPath('data.0.can.renew', false);

        $this->getJson(
            '/api/inscripciones-atenciones/vigencias/panel?estado=vigente',
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo_entidad', 'inscripcion_sala');
    }

    public function test_user_without_branches_gets_clear_error(): void
    {
        $escenario = $this->scenario();
        $this->withoutBranch($escenario['user'], $escenario['branch']);

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel')
            ->assertStatus(403)
            ->assertJsonPath('code', 'SIN_SUCURSALES_ASIGNADAS');
    }

    public function test_user_without_entity_view_permission_gets_clear_error(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsWithPermissions(['vigencia.ver'], $branch);

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel')
            ->assertStatus(403)
            ->assertJsonPath('code', 'SIN_PERMISO_TIPO_ENTIDAD');

        $this->assertNotNull($user->id);
    }

    public function test_reports_support_state_and_period_filters(): void
    {
        $escenario = $this->scenario('administracion');
        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated();
        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated();

        $this->getJson(
            '/api/inscripciones-atenciones/vigencias/reportes?estado=vencido&periodo_desde=2026-10-01&periodo_hasta=2026-10-31',
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.estado_vigencia', 'vencido');
    }
}
