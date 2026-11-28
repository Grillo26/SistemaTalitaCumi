<?php

namespace Tests\Feature\Modules\AgendaTurnos;

final class AgendaColoresTest extends AgendaSemanalTestCase
{
    public function test_area_color_and_contrast_come_from_the_catalog(): void
    {
        $scenario = $this->scenario();

        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
        );

        $this->getJson($this->weekUrl($scenario['branch']->id))
            ->assertOk()
            ->assertJsonPath('data.turnos.0.servicio.area_color', '#F9733F')
            ->assertJsonPath('data.turnos.0.servicio.color_texto', '#101828')
            ->assertJsonPath('data.turnos.0.servicio.area_nombre', $scenario['area']->nombre);
    }

    public function test_invalid_catalog_color_is_not_applied(): void
    {
        $scenario = $this->scenario();
        $scenario['area']->forceFill(['color' => '#ZZZZZZ'])->save();

        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
        );

        $this->getJson($this->weekUrl($scenario['branch']->id))
            ->assertOk()
            ->assertJsonPath('data.turnos.0.servicio.area_color', null)
            ->assertJsonPath('data.turnos.0.servicio.color_texto', null);
    }
}
