<?php

namespace Tests\Feature\Modules\AgendaTurnos;

final class AgendaSinAsignarTest extends AgendaSemanalTestCase
{
    public function test_turn_without_professional_is_marked_and_filterable(): void
    {
        $scenario = $this->scenario();

        $unassigned = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
        );
        $assigned = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-24',
            '09:00',
            '09:50',
        );

        $response = $this->getJson($this->weekUrl($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonPath('data.turnos.0.id', $unassigned->id)
            ->assertJsonPath('data.turnos.0.sin_asignar', true)
            ->assertJsonPath('data.turnos.0.profesional', null);

        $unassignedIds = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['profesional_sin_asignar' => 1],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $assignedIds = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['profesional_id' => $scenario['profesional']->id],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $this->assertSame([$unassigned->id], $unassignedIds);
        $this->assertSame([$assigned->id], $assignedIds);
    }
}
