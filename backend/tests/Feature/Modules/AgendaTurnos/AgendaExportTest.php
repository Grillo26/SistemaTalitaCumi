<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Queries\GetAgendaExportData;

final class AgendaExportTest extends AgendaSemanalTestCase
{
    public function test_export_returns_a_clear_503_without_pdf_infrastructure(): void
    {
        $scenario = $this->scenario();
        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
        );

        $this->getJson('/api/agenda-turnos/agenda-semanal/exportar?'.http_build_query([
            'sucursal_id' => $scenario['branch']->id,
            'semana' => '2026-11-23',
        ]))
            ->assertStatus(503)
            ->assertJsonPath('code', 'EXPORTACION_NO_DISPONIBLE');
    }

    public function test_export_requires_export_permission(): void
    {
        $scenario = $this->scenario();
        $this->actingAsWithPermissions(['turno.ver'], [$scenario['branch']->id]);

        $this->getJson('/api/agenda-turnos/agenda-semanal/exportar?'.http_build_query([
            'sucursal_id' => $scenario['branch']->id,
            'semana' => '2026-11-23',
        ]))
            ->assertStatus(403);
    }

    public function test_export_data_reuses_filters_and_center_configuration(): void
    {
        $scenario = $this->scenario();
        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
        );

        $datos = app(GetAgendaExportData::class)($scenario['user'], [
            'sucursal_id' => $scenario['branch']->id,
            'semana' => '2026-11-23',
            'profesional_id' => $scenario['profesional']->id,
        ]);

        $this->assertSame(1, $datos['agenda']['resumen']['total']);
        $this->assertSame('TalitaCumi', $datos['centro']['nombre']);
        $this->assertNotNull($datos['generado_en']);
        $this->assertSame($scenario['branch']->id, $datos['filtros']['sucursal_id']);
    }
}
