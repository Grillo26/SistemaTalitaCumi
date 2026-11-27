<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Contracts\VigenciaEntidadContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

final class VigenciasPacienteTest extends VigenciasTestCase
{
    public function test_patient_summary_has_counts_and_no_clinical_fields(): void
    {
        $escenario = $this->scenario('recepcion');
        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated();
        $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated();

        $response = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/pacientes/'.$escenario['paciente']->id.'/resumen',
        )
            ->assertOk()
            ->assertJsonPath('data.conteos.vencido', 1)
            ->assertJsonPath('data.conteos.por_vencer', 1)
            ->assertJsonPath('data.conteos.vigente', 0);

        $items = $response->json('data.items');

        $this->assertCount(2, $items);
        $this->assertArrayNotHasKey('diagnostico', $items[0]);
        $this->assertArrayNotHasKey('alergias', $items[0]);
        $this->assertArrayNotHasKey('condiciones_medicas', $items[0]);
    }

    public function test_agenda_contract_uses_turn_date_and_period(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ])->assertCreated()->json('data');

        $contract = app(VigenciaEntidadContract::class);

        $this->assertTrue($contract->puedeRegistrarAsistencia(
            TipoEntidadVigencia::PAQUETE_SESIONES,
            $paquete['id'],
            '2026-11-10',
        ));
        $this->assertFalse($contract->puedeRegistrarAsistencia(
            TipoEntidadVigencia::PAQUETE_SESIONES,
            $paquete['id'],
            '2026-12-01',
        ));
        $this->assertFalse($contract->puedeRegistrarAsistencia(
            TipoEntidadVigencia::PAQUETE_SESIONES,
            $paquete['id'],
            '2026-13-01',
        ));
    }

    public function test_export_returns_clear_503_when_infrastructure_is_missing(): void
    {
        $this->actingAsRole('administracion');

        $this->getJson('/api/inscripciones-atenciones/vigencias/reportes/exportar?formato=pdf')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Exportación PDF/Excel');
    }
}
