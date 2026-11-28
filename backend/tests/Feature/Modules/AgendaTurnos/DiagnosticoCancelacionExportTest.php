<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;

/**
 * 07.05/RF-007 / 07.05/RF-008 / 07.05/RF-009 / 07.05/CU-02 FA-01 /
 * 07.05/CU-03 / 07.05/CA-007 / 07.05/CA-008.
 */
final class DiagnosticoCancelacionExportTest extends DiagnosticosTestCase
{
    public function test_cancelar_conserva_turnos_pasados_y_cancela_futuros(): void
    {
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];
        $primero = $escenario['profesionales_diagnostico'][0];
        $segundo = $escenario['profesionales_diagnostico'][1];

        $futuro = $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $primero->id,
            '2025-12-02',
            '09:00',
            '09:50',
        );
        $pasado = $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $segundo->id,
            '2025-11-29',
            '09:00',
            '09:50',
            EstadoTurno::ASISTIO,
        );

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/cancelar"),
            ['motivo' => 'La familia decidió no continuar con la evaluación.'],
        )
            ->assertOk()
            ->assertJsonPath('data.estado_general', 'cancelado')
            ->assertJsonPath('data.cantidad_cancelados', 1)
            ->assertJsonPath('data.turnos_conservados', 1);

        $this->assertDatabaseHas('turno', [
            'id' => $futuro->id,
            'estado' => 'cancelado',
        ]);
        $this->assertDatabaseHas('turno', [
            'id' => $pasado->id,
            'estado' => 'asistio',
        ]);
        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'cancelado',
            'estado' => 'activa',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'atencion_diagnostico',
            'entidad_id' => $diagnostico->id,
            'accion' => 'CANCELAR',
            'critico' => true,
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'turno',
            'entidad_id' => $futuro->id,
            'accion' => 'CANCELAR',
        ]);

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonPath('data.estado_general', 'cancelado')
            ->assertJsonPath('can.programar', false);
    }

    public function test_cancelar_exige_permiso_de_administracion(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $this->actorConPermisos($escenario['branch'], [
            'asignacion.ver',
            'asignacion.crear',
            'atencion.ver',
        ]);

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/cancelar"),
            ['motivo' => 'Intento de recepción'],
        )->assertForbidden();
    }

    public function test_cancelar_exige_motivo(): void
    {
        $escenario = $this->escenarioDiagnostico(1);

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/cancelar"),
            ['motivo' => ''],
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo']);
    }

    public function test_exportar_csv_audita_filtros_y_cantidad(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->crearTurnoDiagnostico($diagnostico, (int) $profesional->id);

        $response = $this->get(
            $this->diagnosticosUrl('/exportar?desde=2025-11-01&hasta=2025-12-31&formato=csv'),
        );

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $contenido = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString($escenario['paciente']->nombre_completo, $contenido);
        $this->assertStringContainsString($profesional->nombre_completo, $contenido);
        $this->assertStringContainsString('Programado', $contenido);

        $auditoria = BitacoraEntry::query()
            ->where('accion', 'EXPORTAR')
            ->where('entidad', 'exportacion')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('diagnosticos_agenda', $auditoria->datos_nuevos['recurso']);
        $this->assertSame(1, $auditoria->datos_nuevos['cantidad']);
        $this->assertSame('2025-11-01', $auditoria->datos_nuevos['filtros']['desde']);
    }

    public function test_exportacion_pdf_responde_503(): void
    {
        $escenario = $this->escenarioDiagnostico(1);

        $this->getJson(
            $this->diagnosticosUrl('/exportar?desde=2025-11-01&hasta=2025-12-31&formato=pdf'),
        )
            ->assertStatus(503)
            ->assertJsonPath('code', 'EXPORTACION_DIAGNOSTICOS_NO_DISPONIBLE');
    }

    public function test_exportacion_valida_rango_y_permiso(): void
    {
        $escenario = $this->escenarioDiagnostico(1);

        $this->getJson($this->diagnosticosUrl('/exportar?desde=2025-12-31&hasta=2025-11-01'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hasta']);

        $this->actorConPermisos($escenario['branch'], ['asignacion.ver', 'atencion.ver']);

        $this->getJson($this->diagnosticosUrl('/exportar?desde=2025-11-01&hasta=2025-12-31'))
            ->assertForbidden();
    }
}
