<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;

/**
 * Conexión 7.3 con Inscripciones/Estados: pre-chequeo real de turnos futuros.
 */
final class TurnosFuturosContractTest extends PaquetesSesionesTestCase
{
    public function test_package_future_turns_returns_count_and_date_only(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $paquete = $this->crearPaquete($escenario)->assertCreated()->json('data');
        $modelo = PaqueteSesiones::query()->findOrFail($paquete['id']);

        Turno::factory()
            ->paraPaquete($modelo)
            ->paraFecha('2026-12-01')
            ->paraHorario('08:00', '08:50')
            ->estado(EstadoTurno::PROGRAMADO)
            ->create();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$paquete['id'].'/turnos-futuros')
            ->assertOk()
            ->assertJsonPath('data.cantidad', 1)
            ->assertJsonPath('data.fechas.0', '2026-12-01');
    }

    public function test_room_inscription_future_turns_returns_count_and_date_only(): void
    {
        $escenario = $this->scenario('administracion');
        $inscripcion = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        $modelo = InscripcionSala::query()->findOrFail($inscripcion['id']);

        Turno::factory()
            ->paraSala($escenario['sala'])
            ->paraPaciente($modelo->paciente_id)
            ->paraServicio($modelo->servicio_id)
            ->paraFecha('2026-12-01')
            ->paraHorario('08:00', '08:50')
            ->estado(EstadoTurno::PROGRAMADO)
            ->create([
                'sucursal_id' => $escenario['branch']->id,
                'inscripcion_sala_id' => $modelo->id,
            ]);

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$inscripcion['id'].'/turnos-futuros')
            ->assertOk()
            ->assertJsonPath('data.cantidad', 1)
            ->assertJsonPath('data.fechas.0', '2026-12-01');
    }
}
