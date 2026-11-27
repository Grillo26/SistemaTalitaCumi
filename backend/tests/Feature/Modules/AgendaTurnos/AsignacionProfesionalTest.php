<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Models\Turno;
use Illuminate\Support\Str;

/**
 * [Programación de Turnos] RF-007 / RF-008 / RN-005 / CU-03 FA-01.
 */
final class AsignacionProfesionalTest extends ProgramacionTurnosTestCase
{
    public function test_assignment_is_all_or_nothing_when_one_turn_conflicts(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated();

        $turnos = Turno::query()
            ->where('paquete_sesiones_id', $paquete->id)
            ->ordenado()
            ->limit(2)
            ->get();

        $this->assertCount(2, $turnos);

        Turno::factory()
            ->paraPaciente($this->paciente($escenario['branch'])->id)
            ->paraServicio($escenario['servicio']->id)
            ->paraSucursal($escenario['branch']->id)
            ->conProfesional($escenario['profesional']->id)
            ->paraFecha($turnos[0]->fecha->toDateString())
            ->paraHorario('09:00', '09:50')
            ->create();

        $this->postJson('/api/agenda-turnos/programacion-turnos/turnos/asignar-profesional', [
            'profesional_id' => $escenario['profesional']->id,
            'turno_ids' => $turnos->pluck('id')->all(),
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SOLAPAMIENTO_PROFESIONAL');

        foreach ($turnos as $turno) {
            $this->assertDatabaseHas('turno', [
                'id' => $turno->id,
                'profesional_id' => null,
            ]);
        }

        $otroProfesional = $this->profesional($escenario['branch']);

        $response = $this->postJson(
            '/api/agenda-turnos/programacion-turnos/turnos/asignar-profesional',
            [
                'profesional_id' => $otroProfesional->id,
                'turno_ids' => $turnos->pluck('id')->all(),
            ],
        );

        $response->assertOk()->assertJsonPath('data.cantidad', 2);

        foreach ($turnos as $turno) {
            $this->assertDatabaseHas('turno', [
                'id' => $turno->id,
                'profesional_id' => $otroProfesional->id,
            ]);
        }

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'ASIGNAR_PROFESIONAL',
            'entidad' => 'turno',
            'modulo' => 'agenda',
        ]);
    }

    public function test_assignment_by_batch_applies_to_all_its_turns(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $loteId = (int) $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated()->json('data.lote_id');

        $profesional = $this->profesional($escenario['branch']);

        $this->postJson('/api/agenda-turnos/programacion-turnos/turnos/asignar-profesional', [
            'profesional_id' => $profesional->id,
            'lote_generacion_id' => $loteId,
        ])
            ->assertOk()
            ->assertJsonPath('data.cantidad', 9);

        $this->assertSame(9, Turno::query()
            ->where('lote_generacion_id', $loteId)
            ->where('profesional_id', $profesional->id)
            ->count());
    }

    public function test_assignment_rejects_past_or_not_programmed_turns(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated();

        $turno = Turno::query()
            ->where('paquete_sesiones_id', $paquete->id)
            ->ordenado()
            ->firstOrFail();

        $turno->forceFill(['estado' => 'asistio'])->save();

        $profesional = $this->profesional($escenario['branch']);

        $this->postJson('/api/agenda-turnos/programacion-turnos/turnos/asignar-profesional', [
            'profesional_id' => $profesional->id,
            'turno_ids' => [$turno->id],
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ASIGNACION_NO_PERMITIDA');

        $this->assertDatabaseHas('turno', [
            'id' => $turno->id,
            'profesional_id' => null,
            'estado' => 'asistio',
        ]);
    }
}
