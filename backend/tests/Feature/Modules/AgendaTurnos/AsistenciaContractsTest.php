<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RF-002, 07.04/RF-015, 07.04/RN-003, 07.04/RN-009,
 * conexiones con 06.01, 06.02 y 04.04.
 */
final class AsistenciaContractsTest extends AsistenciaTurnosTestCase
{
    public function test_consumo_de_paquete_refleja_asistio_y_falto(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $asistio = $this->turnoPaqueteHoy($paquete, '09:00', '09:50');
        $falto = $this->turnoPaqueteHoy($paquete, '10:00', '10:50');

        $this->registrar($asistio->id, EstadoTurno::ASISTIO);
        $this->registrar($falto->id, EstadoTurno::FALTO);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$paquete->id.'/consumo')
            ->assertOk()
            ->assertJsonPath('data.pactadas', 10)
            ->assertJsonPath('data.realizadas', 1)
            ->assertJsonPath('data.faltadas', 1)
            ->assertJsonPath('data.canceladas', 0)
            ->assertJsonPath('data.saldo', 8);
    }

    public function test_asistencia_de_inscripcion_cuenta_turnos_y_periodo(): void
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

        $this->getJson($this->asistenciaUrl("/turnos/{$turno->id}"))
            ->assertOk()
            ->assertJsonPath('data.efecto.saldo.aplicable', false)
            ->assertJsonPath('data.efecto.saldo.mensaje', 'El turno no pertenece a un paquete; no hay saldo de sesiones que calcular.');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$inscripcion->id.'/asistencia')
            ->assertOk()
            ->assertJsonPath('data.asistencias', 1)
            ->assertJsonPath('data.faltas', 0)
            ->assertJsonPath('data.cancelados', 0)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.periodo_desde', '2025-11-01')
            ->assertJsonPath('data.periodo_hasta', '2025-11-30');
    }

    public function test_ficha_de_paciente_muestra_resumen_de_asistencia_del_periodo_vigente(): void
    {
        $escenario = $this->escenario();
        $this->actorAsistencia($escenario['branch'], extra: ['paciente.ver']);
        $paquete = $this->crearPaqueteVigente($escenario);
        $turno = $this->turnoPaqueteHoy($paquete);

        $this->registrar($turno->id, EstadoTurno::ASISTIO);

        $this->getJson(
            '/api/pacientes/pacientes/'.$escenario['paciente']->id.'/resumen/indicadores',
        )
            ->assertOk()
            ->assertJsonPath('data.bloques.asistencia.estado', 'disponible')
            ->assertJsonPath('data.bloques.asistencia.data.asistidos', 1)
            ->assertJsonPath('data.bloques.asistencia.data.falto', 0)
            ->assertJsonPath('data.bloques.asistencia.data.cancelados', 0)
            ->assertJsonPath('data.bloques.asistencia.data.porcentaje', 100)
            ->assertJsonPath('data.bloques.asistencia.data.periodo_inicio', '2025-11-01')
            ->assertJsonPath('data.bloques.asistencia.data.periodo_fin', '2025-11-30');
    }
}
