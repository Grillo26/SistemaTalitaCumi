<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\TurnoReprogramado;
use App\Modules\AgendaTurnos\Models\Turno;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * [Reprogramación y Cancelación] RF-001 / RF-003 / RF-004 / RF-005 / RF-006 /
 * RF-007 / RF-009 / RF-013 / RF-014 / RF-015 / CA-001 a CA-006, CA-009.
 */
final class ReprogramacionTurnoTest extends CambiosTurnosTestCase
{
    public function test_reprogramacion_exitosa_vincula_original_y_nuevo_ca_001(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario, [
            'profesional_id' => $escenario['profesional']->id,
        ]);
        $original = $this->crearTurnoPaquete($paquete, '2025-12-02', '09:00', '09:50');
        $motivo = $this->motivo('ausencia_profesional');

        $preview = $this->postJson(
            $this->cambiosUrl("/turnos/{$original->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00', $escenario['profesional']->id),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.puede_confirmar', true)
            ->assertJsonPath('data.nuevo.fecha', '2025-12-09')
            ->assertJsonPath('data.nuevo.hora_inicio', '10:00');

        $response = $this->postJson(
            $this->cambiosUrl("/turnos/{$original->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00', $escenario['profesional']->id),
        );

        $response->assertOk()->assertJsonPath('data.turno_nuevo.fecha', '2025-12-09');
        $nuevoId = (int) $response->json('data.turno_nuevo.id');

        $this->assertDatabaseHas('turno', [
            'id' => $original->id,
            'estado' => 'reprogramado',
            'motivo_turno_id' => $motivo->id,
        ]);
        $this->assertDatabaseHas('turno', [
            'id' => $nuevoId,
            'turno_origen_id' => $original->id,
            'paquete_sesiones_id' => $paquete->id,
            'fecha' => '2025-12-09',
            'hora_inicio' => '10:00:00',
            'estado' => 'programado',
        ]);

        $historial = $this->getJson($this->cambiosUrl("/turnos/{$original->id}/historial"))
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->assertSame($nuevoId, $historial->json('data.items.0.turno_vinculado_id'));
        $this->assertSame('REPROGRAMAR', $historial->json('data.items.0.accion'));

        $this->getJson($this->cambiosUrl("/turnos/{$nuevoId}/historial"))
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.accion', 'CREAR_POR_REPROGRAMACION')
            ->assertJsonPath('data.items.0.turno_vinculado_id', $original->id);

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'REPROGRAMAR',
            'entidad' => 'turno',
            'entidad_id' => $original->id,
        ]);
    }

    public function test_motivo_obligatorio_y_texto_de_otro_ca_002(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            ['fecha' => '2025-12-09', 'hora_inicio' => '10:00', 'estado_esperado' => 'programado'],
        )->assertUnprocessable();

        $otro = $this->motivo('otro');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($otro->id, '2025-12-09', '10:00'),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'MOTIVO_OBLIGATORIO');
    }

    public function test_turno_asistido_o_faltado_no_se_reprograma_ni_cancela_ca_003(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete, estado: EstadoTurno::ASISTIO);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_NO_MODIFICABLE')
            ->assertJsonPath('message', 'No se puede reprogramar un turno ya registrado.');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_NO_MODIFICABLE')
            ->assertJsonPath('message', 'No se puede cancelar un turno ya registrado.');
    }

    public function test_conflicto_de_paciente_y_profesional_ca_004(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario, [
            'profesional_id' => $escenario['profesional']->id,
        ]);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('feriado');

        Turno::factory()
            ->paraPaciente($escenario['paciente']->id)
            ->paraServicio($escenario['servicio']->id)
            ->paraSucursal($escenario['branch']->id)
            ->paraFecha('2025-12-09')
            ->paraHorario('10:00', '10:50')
            ->conProfesional($escenario['profesional']->id)
            ->create();

        $preview = $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00', $escenario['profesional']->id),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.puede_confirmar', false);

        $tipos = array_column($preview->json('data.hallazgos'), 'tipo');
        $this->assertContains('paciente', $tipos);
        $this->assertContains('profesional', $tipos);

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00', $escenario['profesional']->id),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICTO_DISPONIBILIDAD');
    }

    public function test_sala_genera_advertencia_y_exige_confirmacion(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $otroServicio = $this->servicio('sala_grupal');
        $inscripcion = $this->crearInscripcionSala($escenario, [
            'servicio_id' => $otroServicio->id,
        ]);
        $turno = $this->crearTurnoSala(
            $escenario['branch'],
            $escenario['sala'],
            $escenario['paciente'],
            $otroServicio->id,
            fecha: '2025-12-02',
            inscripcionSalaId: (int) $inscripcion->id,
        );
        $motivo = $this->motivo('motivo_familiar');

        $this->crearTurnoSala(
            $escenario['branch'],
            $escenario['sala'],
            $this->paciente($escenario['branch']),
            $escenario['servicio']->id,
            fecha: '2025-12-09',
            inicio: '10:00',
            fin: '10:50',
        );

        $preview = $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.puede_confirmar', false)
            ->assertJsonPath('data.requiere_confirmar_advertencias', true);

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'ADVERTENCIAS_CAMBIO_NO_CONFIRMADAS');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00', null, true),
        )->assertOk();
    }

    public function test_entidad_origen_en_pausa_bloquea_reprogramar_pero_permite_cancelar(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario, ['estado' => 'en_pausa']);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ORIGEN_NO_REPROGRAMABLE');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )->assertOk();
    }

    public function test_transaccion_revierte_todo_si_falla_la_bitacora(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');

        Event::listen(
            TurnoReprogramado::class,
            static fn () => throw new RuntimeException('Fallo simulado de bitácora'),
        );

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        )->assertStatus(500);

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'programado',
        ]);
        $this->assertDatabaseCount('turno', 1);
        $this->assertDatabaseMissing('bitacora', [
            'accion' => 'REPROGRAMAR',
            'entidad_id' => $turno->id,
        ]);
    }

    public function test_doble_envio_no_crea_un_segundo_turno(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');
        $payload = $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00');

        $this->postJson($this->cambiosUrl("/turnos/{$turno->id}/reprogramar"), $payload)->assertOk();

        $this->postJson($this->cambiosUrl("/turnos/{$turno->id}/reprogramar"), $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_NO_MODIFICABLE');

        $this->assertSame(2, Turno::query()->count());
    }

    public function test_sucursal_no_asignada_bloquea_la_reprogramacion_ca_009(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $otraSucursal = $this->branch();
        $this->actorCambios($otraSucursal);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-reprogramacion"),
            $this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
        )
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_reprogramar_un_turno_de_sala_no_mueve_a_los_demas(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $otroServicio = $this->servicio('sala_grupal');
        $inscripcion = $this->crearInscripcionSala($escenario, [
            'servicio_id' => $otroServicio->id,
        ]);
        $turno = $this->crearTurnoSala(
            $escenario['branch'],
            $escenario['sala'],
            $escenario['paciente'],
            $otroServicio->id,
            fecha: '2025-12-02',
            inscripcionSalaId: (int) $inscripcion->id,
        );
        $otroPaciente = $this->paciente($escenario['branch']);
        $otroTurno = $this->crearTurnoSala(
            $escenario['branch'],
            $escenario['sala'],
            $otroPaciente,
            $otroServicio->id,
            fecha: '2025-12-02',
        );
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            $this->payloadReprogramacion($motivo->id, '2025-12-10', '10:00'),
        )->assertOk();

        $this->assertDatabaseHas('turno', [
            'id' => $otroTurno->id,
            'estado' => 'programado',
            'fecha' => '2025-12-02',
        ]);
        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'reprogramado',
        ]);
    }

    public function test_no_existe_delete_de_turnos(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);

        $this->deleteJson($this->cambiosUrl("/turnos/{$turno->id}"))->assertStatus(405);
    }
}
