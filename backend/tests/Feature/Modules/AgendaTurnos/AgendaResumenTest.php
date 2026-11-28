<?php

namespace Tests\Feature\Modules\AgendaTurnos;

final class AgendaResumenTest extends AgendaSemanalTestCase
{
    public function test_summary_counts_filtered_visible_turns_by_state(): void
    {
        $scenario = $this->scenario();
        $otherProfessional = $this->profesional($scenario['branch'], 'Profesional ajeno');

        foreach ([
            ['08:00', '08:50', 'programado'],
            ['09:00', '09:50', 'asistio'],
            ['10:00', '10:50', 'falto'],
        ] as [$inicio, $fin, $estado]) {
            $this->turno(
                $scenario['branch'],
                $scenario['sala'],
                $scenario['paciente'],
                $scenario['servicio'],
                $scenario['profesional'],
                '2026-11-24',
                $inicio,
                $fin,
                $estado,
            );
        }

        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $otherProfessional,
            '2026-11-24',
            '11:00',
            '11:50',
            'cancelado',
        );

        $response = $this->getJson($this->weekUrl(
            $scenario['branch']->id,
            ['profesional_id' => $scenario['profesional']->id],
        ));

        $response
            ->assertOk()
            ->assertJsonPath('data.resumen.total', 3)
            ->assertJsonPath('data.resumen.base', 'turnos_filtrados_visibles')
            ->assertJsonPath('data.resumen.por_estado.programado', 1)
            ->assertJsonPath('data.resumen.por_estado.asistio', 1)
            ->assertJsonPath('data.resumen.por_estado.falto', 1)
            ->assertJsonPath('data.resumen.por_estado.cancelado', 0);
    }
}
