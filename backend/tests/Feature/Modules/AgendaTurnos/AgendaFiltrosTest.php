<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

final class AgendaFiltrosTest extends AgendaSemanalTestCase
{
    public function test_filters_by_area_professional_room_service_and_state(): void
    {
        $scenario = $this->scenario();

        $otherArea = $this->area('#671C61');
        $otherService = $this->servicio($otherArea, 'Otro servicio');
        $otherRoom = $this->sala($scenario['branch'], $otherArea, 'Otra sala');
        $otherProfessional = $this->profesional($scenario['branch'], 'Otro profesional');

        $mine = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-24',
            '08:00',
            '08:50',
            EstadoTurno::ASISTIO->value,
        );
        $other = $this->turno(
            $scenario['branch'],
            $otherRoom,
            $scenario['paciente'],
            $otherService,
            $otherProfessional,
            '2026-11-24',
            '10:00',
            '10:50',
        );

        $byProfessional = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['profesional_id' => $scenario['profesional']->id],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $byArea = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['area_id' => $otherArea->id],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $byRoom = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['sala_id' => $scenario['sala']->id],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $byService = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['servicio_id' => $otherService->id],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $byState = collect(
            $this->getJson($this->weekUrl(
                $scenario['branch']->id,
                ['estado' => EstadoTurno::ASISTIO->value],
            ))->assertOk()->json('data.turnos'),
        )->pluck('id')->all();

        $this->assertSame([$mine->id], $byProfessional);
        $this->assertSame([$other->id], $byArea);
        $this->assertSame([$mine->id], $byRoom);
        $this->assertSame([$other->id], $byService);
        $this->assertSame([$mine->id], $byState);
    }

    public function test_sin_asignar_filters_conflict_with_specific_ids(): void
    {
        $scenario = $this->scenario();

        $this->getJson($this->weekUrl($scenario['branch']->id, [
            'profesional_id' => $scenario['profesional']->id,
            'profesional_sin_asignar' => 1,
        ]))->assertStatus(422);

        $this->getJson($this->weekUrl($scenario['branch']->id, [
            'sala_id' => $scenario['sala']->id,
            'sala_sin_asignar' => 1,
        ]))->assertStatus(422);
    }

    public function test_filter_options_include_specials_and_branch_data(): void
    {
        $scenario = $this->scenario();

        $response = $this->getJson($this->optionsUrl($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonPath('data.especiales.profesional_sin_asignar.label', 'Sin asignar')
            ->assertJsonPath('data.especiales.sala_sin_asignar.label', 'Sin sala')
            ->assertJsonPath('disponibilidad.profesionales', true)
            ->assertJsonPath('disponibilidad.salas', true);

        $professionalIds = collect($response->json('data.profesionales'))->pluck('id')->all();
        $roomIds = collect($response->json('data.salas'))->pluck('id')->all();

        $this->assertContains($scenario['profesional']->id, $professionalIds);
        $this->assertContains($scenario['sala']->id, $roomIds);
    }
}
