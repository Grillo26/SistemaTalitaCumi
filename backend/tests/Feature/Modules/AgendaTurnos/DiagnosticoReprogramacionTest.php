<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.05/RF-013 / 07.05/RN-006 / 07.05/CA-006.
 *
 * La reprogramación individual pertenece a 07.3. 07.05 solo verifica que el
 * turno nuevo conserve el vínculo con el diagnóstico, que los demás turnos no
 * cambien y que el estado general se sincronice.
 */
final class DiagnosticoReprogramacionTest extends DiagnosticosTestCase
{
    public function test_reprogramar_un_turno_no_afecta_a_los_demas_y_sincroniza_estado(): void
    {
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];
        $primero = $escenario['profesionales_diagnostico'][0];
        $segundo = $escenario['profesionales_diagnostico'][1];

        $turnoPrimero = $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $primero->id, '2025-12-02', '09:00'),
        )->assertCreated()->json('data.turno.id');

        $turnoSegundo = $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $segundo->id, '2025-12-02', '10:00'),
        )->assertCreated()->json('data.turno.id');

        $this->postJson(
            '/api/agenda-turnos/cambios-turnos/turnos/'.$turnoPrimero.'/reprogramar',
            [
                'fecha' => '2025-12-03',
                'hora_inicio' => '09:00',
                'motivo_id' => $this->motivo('otro')->id,
                'motivo_texto' => 'Reprogramación coordinada con la familia.',
                'estado_esperado' => 'programado',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.turno_original.estado', 'reprogramado')
            ->assertJsonPath('data.turno_nuevo.estado', 'programado')
            ->assertJsonPath('data.turno_nuevo.profesional_id', $primero->id);

        $this->assertDatabaseHas('turno', [
            'id' => $turnoPrimero,
            'estado' => 'reprogramado',
        ]);
        $this->assertDatabaseHas('turno', [
            'id' => $turnoSegundo,
            'estado' => 'programado',
            'fecha' => '2025-12-02',
        ]);
        $this->assertDatabaseHas('turno', [
            'atencion_diagnostico_id' => $diagnostico->id,
            'profesional_id' => $primero->id,
            'estado' => 'programado',
            'fecha' => '2025-12-03',
        ]);
        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'en_proceso',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'turno',
            'entidad_id' => $turnoPrimero,
            'accion' => 'REPROGRAMAR',
        ]);
    }

    public function test_reprogramacion_de_diagnostico_conserva_profesional_y_no_asigna_sala(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];
        $otroProfesional = $this->profesional($escenario['branch']);
        $sala = $this->sala($escenario['branch']);

        $turno = $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $profesional->id,
            '2025-12-02',
            '09:00',
            '09:50',
        );

        $this->getJson('/api/agenda-turnos/cambios-turnos/turnos/'.$turno->id)
            ->assertOk()
            ->assertJsonPath('data.turno.atencion_diagnostico_id', $diagnostico->id)
            ->assertJsonPath('data.turno.sala', null);

        $this->postJson(
            '/api/agenda-turnos/cambios-turnos/turnos/'.$turno->id.'/reprogramar',
            [
                'fecha' => '2025-12-03',
                'hora_inicio' => '09:00',
                'motivo_id' => $this->motivo('otro')->id,
                'motivo_texto' => 'Cambio de fecha.',
                'estado_esperado' => 'programado',
                'profesional_id' => $otroProfesional->id,
                'sala_id' => $sala->id,
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.turno_nuevo.profesional_id', $profesional->id)
            ->assertJsonPath('data.turno_nuevo.sala_id', null);

        $this->assertDatabaseHas('turno', [
            'atencion_diagnostico_id' => $diagnostico->id,
            'profesional_id' => $profesional->id,
            'sala_id' => null,
            'fecha' => '2025-12-03',
            'estado' => 'programado',
        ]);
    }

    public function test_turno_reprogramado_no_cubre_el_puesto_hasta_que_exista_el_nuevo(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];

        $turno = $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $profesional->id,
            '2025-12-02',
            '09:00',
            '09:50',
            EstadoTurno::REPROGRAMADO,
        );

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonPath('data.estado_general', 'incompleto');

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'reprogramado',
        ]);
    }
}
