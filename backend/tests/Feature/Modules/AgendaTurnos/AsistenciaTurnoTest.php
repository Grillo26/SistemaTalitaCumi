<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\AsistenciaRegistrada;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Support\AsistenciaClock;
use App\Modules\Organizacion\Models\Parametro;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * 07.04/RF-001, 07.04/RF-002, 07.04/RF-003, 07.04/RF-007, 07.04/RF-008, 07.04/RF-011, 07.04/RF-012,
 * 07.04/RN-001, 07.04/RN-002, 07.04/RN-005, 07.04/RN-006, 07.04/RN-007, 07.04/RN-009,
 * 07.04/CU-01, 07.04/CA-001, 07.04/CA-002, 07.04/CA-003, 07.04/CA-004, 07.04/CA-007, 07.04/CA-010.
 */
final class AsistenciaTurnoTest extends AsistenciaTurnosTestCase
{
    public function test_registrar_asistio_actualiza_estado_version_y_bitacora(): void
    {
        $escenario = $this->escenario();
        $actor = $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $response = $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.turno.estado', 'asistio')
            ->assertJsonPath('data.turno.version', 2)
            ->assertJsonPath('data.sin_cambios', false)
            ->assertJsonPath('data.accion', 'registro');

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'asistio',
            'version' => 2,
            'usuario_asistencia_id' => $actor->id,
        ]);
        $this->assertNotNull($turno->fresh()->asistencia_registrada_en);

        $bitacora = BitacoraEntry::query()
            ->where('entidad', 'turno')
            ->where('entidad_id', $turno->id)
            ->where('accion', 'ASISTIO')
            ->firstOrFail();

        $this->assertSame('registro', $bitacora->datos_nuevos['evento']);
        $this->assertFalse($bitacora->datos_nuevos['fuera_ventana']);
        $this->assertFalse($bitacora->critico);

        $this->getJson('/api/agenda-turnos/cambios-turnos/turnos/'.$turno->id.'/historial')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.accion', 'ASISTIO');
    }

    public function test_registrar_falto(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('falto'),
        )
            ->assertOk()
            ->assertJsonPath('data.turno.estado', 'falto');

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'FALTO',
            'entidad' => 'turno',
            'entidad_id' => $turno->id,
        ]);
    }

    public function test_mismo_estado_es_idempotente_y_no_duplica_bitacora(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        )->assertOk();

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        )
            ->assertOk()
            ->assertJsonPath('data.sin_cambios', true)
            ->assertJsonPath('data.turno.version', 2);

        $this->assertSame(1, BitacoraEntry::query()
            ->where('entidad', 'turno')
            ->where('entidad_id', $turno->id)
            ->count());
    }

    public function test_estado_distinto_por_registro_inicial_exige_correccion(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('falto'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ASISTENCIA_YA_REGISTRADA')
            ->assertJsonPath('context.estado_actual', 'asistio');

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'asistio',
            'version' => 2,
        ]);
    }

    public function test_cancelado_y_reprogramado_estan_bloqueados(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);

        foreach ([EstadoTurno::CANCELADO, EstadoTurno::REPROGRAMADO] as $estado) {
            $turno = $this->turnoPaqueteHoy($paquete);
            $turno->forceFill(['estado' => $estado->value])->save();

            $this->postJson(
                $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
                $this->payloadRegistro('asistio'),
            )
                ->assertStatus(409)
                ->assertJsonPath('code', 'TURNO_NO_ASISTIBLE');
        }
    }

    public function test_turno_futuro_esta_bloqueado(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->crearTurnoPaquete($paquete, '2025-11-30', '11:00', '11:50');

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_NO_ASISTIBLE');
    }

    public function test_turno_fuera_de_sucursal_responde_404(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);
        $this->actorAsistencia($this->branch());

        $this->getJson($this->asistenciaUrl("/turnos/{$turno->id}"))
            ->assertStatus(404)
            ->assertJsonPath('code', 'TURNO_NOT_FOUND');
    }

    public function test_sin_permiso_de_registro_responde_403(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], without: ['asistencia.registrar']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        )->assertForbidden();
    }

    public function test_no_existe_delete_de_asistencia(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->deleteJson($this->asistenciaUrl("/turnos/{$turno->id}"))->assertStatus(405);
    }

    public function test_bitacora_fail_closed_revierte_la_asistencia(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        Event::listen(
            AsistenciaRegistrada::class,
            static fn () => throw new RuntimeException('Fallo simulado de bitácora'),
        );

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/registrar"),
            $this->payloadRegistro('asistio'),
        )->assertStatus(500);

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'programado',
            'version' => 1,
        ]);
        $this->assertDatabaseMissing('bitacora', [
            'entidad' => 'turno',
            'entidad_id' => $turno->id,
        ]);
    }

    public function test_conflicto_de_version(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 1),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNO_VERSION_CONFLICTO')
            ->assertJsonPath('context.version_actual', 2);
    }

    public function test_correccion_dentro_de_ventana(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 2, 'Error de carga'),
        )
            ->assertOk()
            ->assertJsonPath('data.turno.estado', 'falto')
            ->assertJsonPath('data.turno.version', 3)
            ->assertJsonPath('data.fuera_ventana', false);

        $bitacora = BitacoraEntry::query()
            ->where('entidad', 'turno')
            ->where('entidad_id', $turno->id)
            ->where('accion', 'FALTO')
            ->firstOrFail();

        $this->assertSame('correccion', $bitacora->datos_nuevos['evento']);
        $this->assertFalse($bitacora->datos_nuevos['fuera_ventana']);
        $this->assertSame('Error de carga', $bitacora->motivo);
    }

    public function test_correccion_fuera_de_ventana_bloquea_a_recepcion(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);
        $this->avanzarHoras(25);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 2, 'Error de carga'),
        )
            ->assertStatus(403)
            ->assertJsonPath('code', 'ASISTENCIA_FUERA_DE_VENTANA');

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'estado' => 'asistio',
            'version' => 2,
        ]);
    }

    public function test_correccion_fuera_de_ventana_con_permiso_exige_motivo(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['asistencia.corregir_fuera_ventana']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);
        $this->avanzarHoras(25);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 2),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'MOTIVO_ASISTENCIA_REQUERIDO');
    }

    public function test_correccion_fuera_de_ventana_con_permiso_y_motivo_es_critica(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['asistencia.corregir_fuera_ventana']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);
        $this->avanzarHoras(25);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 2, 'Corrección autorizada por Administración'),
        )
            ->assertOk()
            ->assertJsonPath('data.fuera_ventana', true);

        $bitacora = BitacoraEntry::query()
            ->where('entidad', 'turno')
            ->where('entidad_id', $turno->id)
            ->where('accion', 'FALTO')
            ->firstOrFail();

        $this->assertTrue($bitacora->critico);
        $this->assertTrue($bitacora->datos_nuevos['fuera_ventana']);
        $this->assertSame('correccion_fuera_ventana', $bitacora->datos_nuevos['evento']);

        $this->getJson('/api/agenda-turnos/cambios-turnos/turnos/'.$turno->id.'/historial')
            ->assertOk()
            ->assertJsonPath('data.items.1.fuera_ventana', true)
            ->assertJsonPath('data.items.1.evento', 'correccion_fuera_ventana')
            ->assertJsonPath('data.items.1.motivo_texto', 'Corrección autorizada por Administración');
    }

    public function test_ventana_cero_exige_permiso_especial_desde_el_primer_momento(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        Parametro::query()
            ->where('clave', AsistenciaClock::VENTANA_HORAS)
            ->update(['valor' => '0']);
        Cache::flush();

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turno->id}/corregir"),
            $this->payloadCorreccion('falto', 2),
        )
            ->assertStatus(403)
            ->assertJsonPath('code', 'ASISTENCIA_FUERA_DE_VENTANA');
    }

    private function avanzarHoras(int $horas): void
    {
        $ahora = CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz')
            ->addHours($horas);

        Carbon::setTestNow($ahora);
        CarbonImmutable::setTestNow($ahora);
    }
}
