<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Cache;

final class AgendaDomingoTest extends AgendaSemanalTestCase
{
    public function test_sunday_is_hidden_by_default_and_reported_when_it_has_turns(): void
    {
        $scenario = $this->scenario();

        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-29',
        );

        $response = $this->getJson($this->weekUrl($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.turnos')
            ->assertJsonPath('data.semana.dias.6.visible', false)
            ->assertJsonPath('data.avisos.0.codigo', 'TURNOS_DIAS_NO_VISIBLES')
            ->assertJsonPath('data.avisos.0.cantidad', 1)
            ->assertJsonPath('data.avisos.0.dias.0', '2026-11-29');
    }

    public function test_sunday_can_be_enabled_by_configuration(): void
    {
        $scenario = $this->scenario();

        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-29',
        );

        Parametro::query()
            ->where('clave', 'agenda.dias_semana_habilitados')
            ->update(['valor' => '1,2,3,4,5,6,7']);
        Cache::flush();

        $response = $this->getJson($this->weekUrl($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.turnos')
            ->assertJsonPath('data.semana.dias.6.visible', true)
            ->assertJsonPath('data.avisos', [])
            ->assertJsonPath('data.resumen.total', 1);
    }
}
