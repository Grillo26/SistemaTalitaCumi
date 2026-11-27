<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RF-009, 07.04/RF-010, 07.04/RF-014, 07.04/CU-03,
 * 07.04/CA-008, 07.04/CA-009.
 */
final class AsistenciaReportesTest extends AsistenciaTurnosTestCase
{
    public function test_indicadores_agrupados_por_servicio(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $asistio = $this->turnoPaqueteHoy($paquete, '09:00', '09:50');
        $falto = $this->turnoPaqueteHoy($paquete, '10:00', '10:50');

        $this->registrar($asistio->id, EstadoTurno::ASISTIO);
        $this->registrar($falto->id, EstadoTurno::FALTO);

        $response = $this->getJson($this->asistenciaUrl(
            '/indicadores?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-01&periodo_hasta=2025-11-30&agrupacion=servicio',
        ));

        $response
            ->assertOk()
            ->assertJsonPath('data.totales.asistidos', 1)
            ->assertJsonPath('data.totales.faltos', 1)
            ->assertJsonPath('data.totales.porcentaje', 50)
            ->assertJsonPath('data.filas.0.etiqueta', $escenario['servicio']->nombre)
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_indicadores_rechazan_rango_invalido(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);

        $this->getJson($this->asistenciaUrl(
            '/indicadores?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-30&periodo_hasta=2025-11-01&agrupacion=servicio',
        ))
            ->assertStatus(422);
    }

    public function test_indicadores_bloquean_sucursal_no_asignada(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($this->branch());

        $this->getJson($this->asistenciaUrl(
            '/indicadores?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-01&periodo_hasta=2025-11-30&agrupacion=servicio',
        ))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_resumen_diario_por_sucursal(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $asistio = $this->turnoPaqueteHoy($paquete, '09:00', '09:50');
        $falto = $this->turnoPaqueteHoy($paquete, '10:00', '10:50');
        $pendiente = $this->turnoPaqueteHoy($paquete, '11:00', '11:50');

        $this->registrar($asistio->id, EstadoTurno::ASISTIO);
        $this->registrar($falto->id, EstadoTurno::FALTO);

        $this->getJson($this->asistenciaUrl(
            '/resumen-diario?fecha=2025-11-30&sucursal_id='.$escenario['branch']->id,
        ))
            ->assertOk()
            ->assertJsonPath('data.conteos.atendidos', 1)
            ->assertJsonPath('data.conteos.faltados', 1)
            ->assertJsonPath('data.conteos.pendientes', 1)
            ->assertJsonPath('data.conteos.cancelados', 0)
            ->assertJsonPath('data.sucursal.id', $escenario['branch']->id);
    }

    public function test_resumen_diario_incluye_detalle_por_sala(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $inscripcion = $this->crearInscripcionSala($escenario, [
            'fecha_inicio' => '2025-11-01',
            'fecha_fin' => '2025-11-30',
        ]);
        $turno = $this->crearTurnoSala(
            $escenario['branch'],
            $escenario['sala'],
            $escenario['paciente'],
            $escenario['servicio']->id,
            '2025-11-30',
            '09:00',
            '09:50',
            inscripcionSalaId: (int) $inscripcion->id,
        );

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->getJson($this->asistenciaUrl(
            '/resumen-diario?fecha=2025-11-30&sucursal_id='.$escenario['branch']->id,
        ))
            ->assertOk()
            ->assertJsonPath('data.por_sala.0.sala_id', $escenario['sala']->id)
            ->assertJsonPath('data.por_sala.0.atendidos', 1)
            ->assertJsonPath('data.por_sala.0.total', 1);
    }

    public function test_exportacion_csv_auditada_y_sin_detalle_por_paciente(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['asistencia.exportar']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $response = $this->get($this->asistenciaUrl(
            '/exportar?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-01&periodo_hasta=2025-11-30&agrupacion=servicio&formato=csv',
        ));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $contenido = $response->streamedContent();
        $this->assertStringContainsString('Sucursal;Agrupación', $contenido);
        $this->assertStringContainsString('100.00', $contenido);

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'EXPORTAR',
            'entidad' => 'exportacion',
            'modulo' => 'auditoria',
        ]);
    }

    public function test_exportacion_por_paciente_se_rechaza_por_privacidad(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['asistencia.exportar']);

        $this->getJson($this->asistenciaUrl(
            '/exportar?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-01&periodo_hasta=2025-11-30&agrupacion=paciente&formato=csv',
        ))
            ->assertStatus(422);
    }

    public function test_exportacion_pdf_responde_503(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['asistencia.exportar']);

        $this->getJson($this->asistenciaUrl(
            '/exportar?sucursal_id='.$escenario['branch']->id
            .'&periodo_desde=2025-11-01&periodo_hasta=2025-11-30&agrupacion=servicio&formato=pdf',
        ))
            ->assertStatus(503)
            ->assertJsonPath('code', 'EXPORTACION_ASISTENCIA_NO_DISPONIBLE');
    }
}
