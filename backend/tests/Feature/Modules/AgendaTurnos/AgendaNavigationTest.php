<?php

namespace Tests\Feature\Modules\AgendaTurnos;

final class AgendaNavigationTest extends AgendaSemanalTestCase
{
    public function test_current_next_and_sunday_input_normalize_to_the_expected_week(): void
    {
        $scenario = $this->scenario();

        $current = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-24',
        );
        $next = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-12-01',
        );

        $this->getJson($this->weekUrl($scenario['branch']->id))
            ->assertOk()
            ->assertJsonPath('data.semana.inicio', '2026-11-23')
            ->assertJsonPath('data.turnos.0.id', $current->id);

        $this->getJson($this->weekUrl($scenario['branch']->id, ['semana' => '2026-12-02']))
            ->assertOk()
            ->assertJsonPath('data.semana.inicio', '2026-11-30')
            ->assertJsonPath('data.turnos.0.id', $next->id);

        $this->getJson($this->weekUrl($scenario['branch']->id, ['semana' => '2026-11-29']))
            ->assertOk()
            ->assertJsonPath('data.semana.inicio', '2026-11-23')
            ->assertJsonPath('data.turnos.0.id', $current->id);
    }

    public function test_invalid_week_date_returns_422(): void
    {
        $scenario = $this->scenario();

        $this->getJson($this->weekUrl($scenario['branch']->id, ['semana' => '2026-13-40']))
            ->assertStatus(422);
    }
}
