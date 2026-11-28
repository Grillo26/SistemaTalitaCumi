<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Models\BitacoraEntry;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RF-005 / 07.05/RF-011 / 07.05/RF-012 /
 * 07.05/RF-015 / 07.05/RN-001 / 07.05/RN-002 / 07.05/RN-003 / 07.05/RN-004 /
 * 07.05/RN-005 / 07.05/RN-009 / 07.05/CA-001 / 07.05/CA-003 / 07.05/CA-004.
 */
final class DiagnosticoProgramacionTest extends DiagnosticosTestCase
{
    public function test_programa_turnos_por_profesional_y_calcula_estado_general(): void
    {
        $escenario = $this->escenarioDiagnostico(3);
        $diagnostico = $escenario['diagnostico'];
        $primero = $escenario['profesionales_diagnostico'][0];
        $segundo = $escenario['profesionales_diagnostico'][1];
        $tercero = $escenario['profesionales_diagnostico'][2];

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $primero->id, '2025-12-02', '09:00'),
        )
            ->assertCreated()
            ->assertJsonPath('data.turno.estado', 'programado')
            ->assertJsonPath('data.estado_general', 'incompleto');

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $segundo->id, '2025-12-02', '10:00'),
        )
            ->assertCreated()
            ->assertJsonPath('data.estado_general', 'incompleto');

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $tercero->id, '2025-12-02', '11:00'),
        )
            ->assertCreated()
            ->assertJsonPath('data.estado_general', 'en_proceso');

        $this->assertDatabaseCount('turno', 3);
        $this->assertDatabaseHas('turno', [
            'atencion_diagnostico_id' => $diagnostico->id,
            'profesional_id' => $primero->id,
            'estado' => 'programado',
            'duracion_minutos' => 50,
        ]);
        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'en_proceso',
        ]);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'turno',
            'accion' => 'PROGRAMAR_DIAGNOSTICO',
            'modulo' => 'agenda',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'atencion_diagnostico',
            'entidad_id' => $diagnostico->id,
            'accion' => 'ACTUALIZAR_ESTADO_GENERAL',
            'critico' => true,
        ]);
    }

    public function test_profesional_no_asociado_es_rechazado(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $ajeno = $this->profesional($escenario['branch']);

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $ajeno->id),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_NO_ASIGNADO_AL_DIAGNOSTICO');
    }

    public function test_profesional_con_turno_solapado_en_otra_sucursal_es_rechazado(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $profesional = $escenario['profesionales_diagnostico'][0];
        $otraSucursal = $this->branch();
        $otroPaciente = $this->paciente($otraSucursal);

        $this->crearTurnoDeOtroPaciente(
            $otraSucursal,
            $otroPaciente,
            (int) $escenario['servicio_diagnostico']->id,
            (int) $profesional->id,
            '2025-12-02',
            '09:00',
            '09:50',
        );

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2025-12-02', '09:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'DISPONIBILIDAD_DIAGNOSTICO')
            ->assertJsonFragment(['El profesional ya tiene un turno en ese horario.']);
    }

    public function test_paciente_con_turno_solapado_es_rechazado(): void
    {
        $escenario = $this->escenarioDiagnostico(2);
        $candidato = $escenario['profesionales_diagnostico'][0];
        $ocupado = $escenario['profesionales_diagnostico'][1];

        $this->crearTurnoDeOtroPaciente(
            $escenario['branch'],
            $escenario['paciente'],
            (int) $escenario['servicio_diagnostico']->id,
            (int) $ocupado->id,
            '2025-12-02',
            '09:00',
            '09:50',
        );

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $candidato->id, '2025-12-02', '09:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'DISPONIBILIDAD_DIAGNOSTICO')
            ->assertJsonFragment(['El paciente ya tiene un turno en ese horario.']);
    }

    public function test_un_profesional_solo_puede_tener_un_turno_vigente_por_diagnostico(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2025-12-02', '09:00'),
        )->assertCreated();

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2025-12-03', '09:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_DIAGNOSTICO_DUPLICADO');
    }

    public function test_fecha_fuera_del_periodo_es_rechazada(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2026-01-05', '09:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'DIAGNOSTICO_FUERA_DE_PERIODO');
    }

    public function test_dia_no_habilitado_y_horario_fuera_de_agenda_son_rechazados(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2025-12-07', '09:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'DISPONIBILIDAD_DIAGNOSTICO');

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $profesional->id, '2025-12-02', '06:30'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'DISPONIBILIDAD_DIAGNOSTICO');
    }

    public function test_cierre_completo_bloquea_sin_asistencias(): void
    {
        $this->viajarA('2025-12-01 10:00:00');
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][0]->id, '2025-12-01', '08:00'),
        )->assertCreated();
        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][1]->id, '2025-12-01', '09:00'),
        )->assertCreated();

        $this->patchJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/estado-general"),
            ['estado_general' => 'completo', 'motivo' => 'Cierre administrativo'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'CIERRE_DIAGNOSTICO_INCOMPLETO');

        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'en_proceso',
        ]);
    }

    public function test_asistencia_sincroniza_estado_general_hasta_completo(): void
    {
        $this->viajarA('2025-12-01 10:00:00');
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];

        $primero = $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][0]->id, '2025-12-01', '08:00'),
        )->assertCreated()->json('data.turno.id');
        $segundo = $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][1]->id, '2025-12-01', '09:00'),
        )->assertCreated()->json('data.turno.id');

        $this->postJson('/api/agenda-turnos/asistencia/turnos/'.$primero.'/registrar', [
            'estado' => 'asistio',
        ])->assertOk();

        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'en_proceso',
        ]);

        $this->postJson('/api/agenda-turnos/asistencia/turnos/'.$segundo.'/registrar', [
            'estado' => 'asistio',
        ])->assertOk();

        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'completo',
        ]);

        $cambios = BitacoraEntry::query()
            ->where('entidad', 'atencion_diagnostico')
            ->where('entidad_id', $diagnostico->id)
            ->where('accion', 'ACTUALIZAR_ESTADO_GENERAL')
            ->pluck('datos_nuevos')
            ->map(static fn (array $datos): string => (string) $datos['estado_general'])
            ->all();

        $this->assertContains('en_proceso', $cambios);
        $this->assertContains('completo', $cambios);
    }

    public function test_actualizacion_manual_solo_acepta_el_estado_calculado(): void
    {
        $this->viajarA('2025-12-01 10:00:00');
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];

        $this->patchJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/estado-general"),
            ['estado_general' => 'en_proceso', 'motivo' => 'Intento manual'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESTADO_GENERAL_INVALIDO');

        $turnoId = $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][0]->id, '2025-12-01', '08:00'),
        )->assertCreated()->json('data.turno.id');

        $this->postJson('/api/agenda-turnos/asistencia/turnos/'.$turnoId.'/registrar', [
            'estado' => 'asistio',
        ])->assertOk();

        $this->assertDatabaseHas('atencion_diagnostico', [
            'id' => $diagnostico->id,
            'estado_general' => 'completo',
        ]);

        $this->patchJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/estado-general"),
            ['estado_general' => 'en_proceso', 'motivo' => 'Retroceso manual'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESTADO_GENERAL_INVALIDO');
    }

    public function test_permiso_de_programacion_es_requerido(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $this->actorDiagnosticos($escenario['branch'], [], ['asignacion.crear']);

        $this->postJson(
            $this->diagnosticosUrl("/{$escenario['diagnostico']->id}/turnos"),
            $this->payloadProgramacion((int) $escenario['profesionales_diagnostico'][0]->id),
        )->assertForbidden();
    }
}
