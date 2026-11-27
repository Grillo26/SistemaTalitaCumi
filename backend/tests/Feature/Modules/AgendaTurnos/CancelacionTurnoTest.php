<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\TurnosCanceladosMasivamente;
use App\Modules\AgendaTurnos\Support\WhatsappTurnoLink;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;
use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * [Reprogramación y Cancelación] RF-002 / RF-008 / RF-010 / RF-011 / RF-012 /
 * RF-015 / RN-006 / RN-009 / RN-010 / CA-005 / CA-007 / CA-008 / CA-009 /
 * CA-010.
 */
final class CancelacionTurnoTest extends CambiosTurnosTestCase
{
    public function test_cancelacion_muestra_saldo_y_guarda_snapshot_ca_005(): void
    {
        $escenario = $this->escenario();
        $actor = $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('enfermedad_paciente');
        $motivo->update(['descuenta_saldo' => true]);

        $this->app->instance(ConsumoPaqueteContract::class, new class implements ConsumoPaqueteContract
        {
            public function disponible(): bool
            {
                return true;
            }

            public function resumen(int $paqueteId): ConsumoPaqueteResumen
            {
                return new ConsumoPaqueteResumen(10, 2, 1, 0);
            }
        });

        $preview = $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-cancelacion"),
            $this->payloadCancelacion($motivo->id),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.saldo.disponible', true)
            ->assertJsonPath('data.saldo.saldo_actual', 7)
            ->assertJsonPath('data.saldo.saldo_resultante', 6)
            ->assertJsonPath('data.consume_saldo', true);

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )->assertOk();

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'cancelado',
            'consume_saldo' => true,
            'cancelado_por_usuario_id' => $actor->id,
        ]);

        $motivo->update(['descuenta_saldo' => false]);

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'consume_saldo' => true,
        ]);
    }

    public function test_sin_consumo_disponible_muestra_no_disponible_y_cancela_igual(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');

        $preview = $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/preview-cancelacion"),
            $this->payloadCancelacion($motivo->id),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.saldo.disponible', false)
            ->assertJsonPath('data.saldo.mensaje', 'Efecto en saldo no disponible.');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )->assertOk();
    }

    public function test_cancelacion_masiva_preview_ejecucion_y_conjunto_cambiado_ca_007(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $motivo = $this->motivo('enfermedad_paciente');
        $elegible1 = $this->crearTurnoPaquete($paquete, '2025-12-09');
        $elegible2 = $this->crearTurnoPaquete($paquete, '2025-12-11');
        $asistio = $this->crearTurnoPaquete($paquete, '2025-12-16', estado: EstadoTurno::ASISTIO);

        $this->getJson("/api/agenda-turnos/cambios-turnos/pacientes/{$escenario['paciente']->id}/origenes-cancelables")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paquete->id)
            ->assertJsonPath('data.0.elegibles', 2)
            ->assertJsonPath('data.0.excluidos', 1);

        $entidades = [['tipo' => 'paquete_sesiones', 'id' => $paquete->id]];

        $this->postJson(
            $this->cambiosUrl('/cancelacion-masiva/preview'),
            $this->payloadMasiva($escenario['paciente']->id, $entidades, $motivo->id),
        )
            ->assertOk()
            ->assertJsonPath('data.cantidades.elegibles', 2)
            ->assertJsonPath('data.cantidades.excluidos', 1)
            ->assertJsonPath('data.puede_confirmar', true);

        $ids = [$elegible1->id, $elegible2->id];

        $response = $this->postJson($this->cambiosUrl('/cancelacion-masiva'), [
            ...$this->payloadMasiva($escenario['paciente']->id, $entidades, $motivo->id),
            'turno_ids' => $ids,
            'confirmar' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.cantidad', 2);
        $lote = (string) $response->json('data.lote_id');
        $this->assertNotSame('', $lote);

        $this->assertDatabaseHas('turno', ['id' => $elegible1->id, 'estado' => 'cancelado']);
        $this->assertDatabaseHas('turno', ['id' => $elegible2->id, 'estado' => 'cancelado']);
        $this->assertDatabaseHas('turno', ['id' => $asistio->id, 'estado' => 'asistio']);

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'CANCELAR_MASIVO',
            'entidad' => 'turno_cancelacion_masiva',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'CANCELAR_MASIVO',
            'entidad' => 'turno',
            'entidad_id' => $elegible1->id,
        ]);

        $this->postJson($this->cambiosUrl('/cancelacion-masiva'), [
            ...$this->payloadMasiva($escenario['paciente']->id, $entidades, $motivo->id),
            'turno_ids' => $ids,
            'confirmar' => true,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONJUNTO_CANCELACION_CAMBIO');
    }

    public function test_cancelacion_masiva_bloquea_sucursal_no_asignada_ca_009(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);
        $this->crearTurnoPaquete($paquete);
        $otraSucursal = $this->branch();
        $this->actorCambios($otraSucursal);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson($this->cambiosUrl('/cancelacion-masiva/preview'), $this->payloadMasiva(
            $escenario['paciente']->id,
            [['tipo' => 'paquete_sesiones', 'id' => $paquete->id]],
            $motivo->id,
        ))
            ->assertStatus(403)
            ->assertJsonPath('code', 'CANCELACION_MASIVA_FUERA_DE_SUCURSAL');
    }

    public function test_whatsapp_genera_enlace_sin_envio_automatico_ca_010(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);

        $escenario['tutor']->update(['telefono' => '71234567']);
        Parametro::query()
            ->where('clave', WhatsappTurnoLink::PREFIJO_PAIS)
            ->update(['valor' => '591']);

        $response = $this->getJson(
            $this->cambiosUrl("/turnos/{$turno->id}/contacto-whatsapp?accion=reprogramado"),
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.disponible', true)
            ->assertJsonPath('data.telefono', '71234567');

        $this->assertStringContainsString('wa.me/59171234567', (string) $response->json('data.whatsapp_url'));
    }

    public function test_reporte_de_cambios_filtra_y_exportacion_no_disponible(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )->assertOk();

        $this->getJson($this->cambiosUrl('/reporte-cambios?tipo_cambio=cancelado'))
            ->assertOk()
            ->assertJsonPath('conteos.cancelados', 1)
            ->assertJsonCount(1, 'data');

        $this->getJson($this->cambiosUrl('/reporte-cambios/exportar?tipo_cambio=cancelado'))
            ->assertStatus(503)
            ->assertJsonPath('code', 'EXPORTACION_CAMBIOS_NO_DISPONIBLE');
    }

    public function test_bitacora_de_cancelacion_ca_008(): void
    {
        $escenario = $this->escenario();
        $user = $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('motivo_familiar');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/cancelar"),
            $this->payloadCancelacion($motivo->id),
        )->assertOk();

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'CANCELAR',
            'entidad' => 'turno',
            'entidad_id' => $turno->id,
            'usuario_id' => $user->id,
            'motivo' => $motivo->nombre,
            'modulo' => 'agenda',
        ]);
    }

    public function test_cancelacion_masiva_revierte_todo_si_falla_la_bitacora(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $motivo = $this->motivo('motivo_familiar');
        $primero = $this->crearTurnoPaquete($paquete, '2025-12-09');
        $segundo = $this->crearTurnoPaquete($paquete, '2025-12-11');
        $entidades = [['tipo' => 'paquete_sesiones', 'id' => $paquete->id]];

        Event::listen(
            TurnosCanceladosMasivamente::class,
            static fn () => throw new RuntimeException('Fallo simulado de bitácora masiva'),
        );

        $this->postJson($this->cambiosUrl('/cancelacion-masiva'), [
            ...$this->payloadMasiva($escenario['paciente']->id, $entidades, $motivo->id),
            'turno_ids' => [$primero->id, $segundo->id],
            'confirmar' => true,
        ])->assertStatus(500);

        $this->assertDatabaseHas('turno', ['id' => $primero->id, 'estado' => 'programado']);
        $this->assertDatabaseHas('turno', ['id' => $segundo->id, 'estado' => 'programado']);
        $this->assertDatabaseMissing('bitacora', [
            'accion' => 'CANCELAR_MASIVO',
            'entidad' => 'turno_cancelacion_masiva',
        ]);
    }

    public function test_historial_muestra_cadena_de_reprogramaciones_y_cancelacion_ca_006(): void
    {
        $escenario = $this->escenario();
        $this->actorCambios($escenario['branch']);
        $paquete = $this->crearPaquete($escenario);
        $turno = $this->crearTurnoPaquete($paquete);
        $motivo = $this->motivo('otro');
        $texto = 'Cambio acordado con la familia';

        $reprogramado = $this->postJson(
            $this->cambiosUrl("/turnos/{$turno->id}/reprogramar"),
            [
                ...$this->payloadReprogramacion($motivo->id, '2025-12-09', '10:00'),
                'motivo_texto' => $texto,
            ],
        )->assertOk();

        $nuevo = (int) $reprogramado->json('data.turno_nuevo.id');

        $this->postJson(
            $this->cambiosUrl("/turnos/{$nuevo}/cancelar"),
            $this->payloadCancelacion($this->motivo('motivo_familiar')->id),
        )->assertOk();

        $this->getJson($this->cambiosUrl("/turnos/{$nuevo}/historial"))
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.accion', 'CREAR_POR_REPROGRAMACION')
            ->assertJsonPath('data.items.1.accion', 'CANCELAR');
    }
}
