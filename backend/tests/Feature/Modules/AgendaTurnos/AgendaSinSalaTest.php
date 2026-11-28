<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Pacientes\Models\Tutor;

final class AgendaSinSalaTest extends AgendaSemanalTestCase
{
    public function test_individual_package_turn_is_grouped_and_filtered_as_sin_sala(): void
    {
        $scenario = $this->scenario();
        $tutor = Tutor::factory()->create([
            'sucursal_registro_id' => $scenario['branch']->id,
            'activo' => true,
        ]);
        $categoria = CategoriaTarifa::factory()->create();
        $tarifa = Tarifa::factory()->create([
            'servicio_id' => $scenario['servicio']->id,
            'categoria_tarifa_id' => $categoria->id,
            'sucursal_id' => $scenario['branch']->id,
            'monto' => '500.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
            'activo' => true,
        ]);
        $paquete = PaqueteSesiones::query()->create([
            'paciente_id' => $scenario['paciente']->id,
            'tutor_responsable_id' => $tutor->id,
            'sucursal_id' => $scenario['branch']->id,
            'servicio_id' => $scenario['servicio']->id,
            'profesional_id' => null,
            'tarifa_id' => $tarifa->id,
            'categoria_tarifa_id' => $categoria->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => '1',
            'hora' => '10:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-12-31',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
        ]);

        $turno = Turno::factory()
            ->paraPaquete($paquete)
            ->paraFecha('2026-11-24')
            ->paraHorario('10:00', '10:50')
            ->create(['sucursal_id' => $scenario['branch']->id]);

        $response = $this->getJson($this->weekUrl(
            $scenario['branch']->id,
            ['sala_sin_asignar' => 1],
        ));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.turnos')
            ->assertJsonPath('data.turnos.0.id', $turno->id)
            ->assertJsonPath('data.turnos.0.sala', null)
            ->assertJsonPath('data.turnos.0.sin_sala', true);

        $this->getJson($this->weekUrl(
            $scenario['branch']->id,
            ['sala_id' => $scenario['sala']->id],
        ))->assertOk()->assertJsonCount(0, 'data.turnos');
    }
}
